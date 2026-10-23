<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/WaveGateway.php';
require_once __DIR__ . '/OrangeMoneyGateway.php';
require_once __DIR__ . '/MtnMoMoGateway.php';
require_once __DIR__ . '/MoovMoneyGateway.php';
require_once __DIR__ . '/StripeCardGateway.php';
require_once __DIR__ . '/PayPalGateway.php';
require_once __DIR__ . '/CryptoGateway.php';

/**
 * Gestionnaire Central des Paiements OSHDI
 * Coordonne les passerelles, la persistance en base et les webhooks
 */
class PaymentManager {
    protected static $instance = null;
    protected $config;
    protected $pdo;
    protected $gateways = [];

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../../config/payment_config.php';
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConfig() {
        return $this->config;
    }

    /**
     * Récupère l'instance d'une passerelle par son identifiant
     * @param string $method wave, orange, mtn, moov, card, paypal, crypto
     * @return PaymentGatewayInterface
     */
    public function getGateway($method) {
        $method = strtolower(trim($method));

        if (isset($this->gateways[$method])) {
            return $this->gateways[$method];
        }

        switch ($method) {
            case 'wave':
                $gateway = new WaveGateway($this->config);
                break;
            case 'orange':
                $gateway = new OrangeMoneyGateway($this->config);
                break;
            case 'mtn':
                $gateway = new MtnMoMoGateway($this->config);
                break;
            case 'moov':
                $gateway = new MoovMoneyGateway($this->config);
                break;
            case 'card':
                $gateway = new StripeCardGateway($this->config);
                break;
            case 'paypal':
                $gateway = new PayPalGateway($this->config);
                break;
            case 'crypto':
                $gateway = new CryptoGateway($this->config);
                break;
            default:
                throw new InvalidArgumentException("Passerelle de paiement non reconnue : '$method'");
        }

        $this->gateways[$method] = $gateway;
        return $gateway;
    }

    /**
     * Génère une référence de paiement unique et traçable
     */
    public function generateReference($type = 'don') {
        $prefixMap = [
            'don'               => 'OSHDI-DON',
            'formation'         => 'OSHDI-FOR',
            'souscription'      => 'OSHDI-SUB',
            'espace_partenaire' => 'OSHDI-PUB',
        ];
        $prefix = $prefixMap[$type] ?? 'OSHDI-PAY';
        return $prefix . '-' . date('Ymd') . '-' . mt_rand(100000, 999999);
    }

    /**
     * Initie un paiement auprès du réseau sélectionné et enregistre la transaction
     */
    public function initiate(array $params) {
        $method = strtolower($params['payment_method'] ?? 'wave');
        $type = $params['type'] ?? 'don';
        $amount = floatval($params['amount'] ?? 0);
        $reference = $params['reference'] ?? $this->generateReference($type);
        $params['reference'] = $reference;

        $gateway = $this->getGateway($method);

        // Enregistrement initial de la transaction en base (statut: en_attente)
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions_paiements 
                (reference, type_commande, item_id, item_title, montant, devise, moyen_paiement, statut, donateur_nom, donateur_email, donateur_telephone, details_requete)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reference,
                $type,
                intval($params['item_id'] ?? 0),
                $params['item_title'] ?? 'Contribution OSHDI',
                $amount,
                'FCFA',
                $method,
                $params['payer_name'] ?? 'Donateur OSHDI',
                $params['payer_email'] ?? 'contact@oshdi.org',
                $params['payer_phone'] ?? '',
                json_encode($params, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (Exception $e) {
            error_log("Erreur insertion transaction_paiement: " . $e->getMessage());
        }

        // Appel de la passerelle spécifique
        $response = $gateway->initiatePayment($params);
        $response['reference'] = $reference;
        $response['method'] = $method;

        // Mise à jour de la référence réseau dans la base
        if (!empty($response['gateway_ref'])) {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE transactions_paiements 
                    SET passerelle_ref = ?, details_reponse = ? 
                    WHERE reference = ?
                ");
                $stmt->execute([
                    $response['gateway_ref'],
                    json_encode($response, JSON_UNESCAPED_UNICODE),
                    $reference
                ]);
            } catch (Exception $e) {
                // Non bloquant
            }
        }

        // Si la passerelle a validé directement (ou mode démo actif), confirmer immédiatement la commande
        if (($response['action'] ?? '') === 'redirect' && !empty($response['is_demo'])) {
            $this->confirmTransaction($reference, $response['gateway_ref'] ?? null, $response);
        }

        return $response;
    }

    /**
     * Confirme et valide une transaction (par Webhook ou retour navigateur)
     */
    public function confirmTransaction($reference, $gatewayRef = null, array $details = []) {
        if (empty($reference)) return false;

        try {
            // 1. Récupération de la transaction
            $stmt = $this->pdo->prepare("SELECT * FROM transactions_paiements WHERE reference = ?");
            $stmt->execute([$reference]);
            $tx = $stmt->fetch();

            if (!$tx) {
                return false;
            }

            // Si déjà validée, éviter les doublons
            if ($tx['statut'] === 'valide') {
                return true;
            }

            // 2. Mise à jour statut transaction
            $upStmt = $this->pdo->prepare("
                UPDATE transactions_paiements 
                SET statut = 'valide', date_validation = datetime('now'), passerelle_ref = COALESCE(?, passerelle_ref), details_reponse = ?
                WHERE reference = ?
            ");
            $upStmt->execute([
                $gatewayRef,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                $reference
            ]);

            // 3. Déclenchement de la logique métier selon le type
            $type = $tx['type_commande'];
            $method = $tx['moyen_paiement'];
            global $AVAILABLE_PAYMENTS;
            $methodName = $AVAILABLE_PAYMENTS[$method]['name'] ?? ucfirst($method);

            if ($type === 'don') {
                // Insertion dans dons_offrandes pour affichage du reçu officiel
                $chk = $this->pdo->prepare("SELECT id FROM dons_offrandes WHERE reference_recu = ?");
                $chk->execute([$reference]);
                if (!$chk->fetch()) {
                    $reqData = json_decode($tx['details_requete'] ?? '{}', true);
                    $ins = $this->pdo->prepare("
                        INSERT INTO dons_offrandes 
                        (donateur_nom, donateur_email, donateur_telephone, type_don, montant, devise, moyen_paiement, reference_recu, message_intention)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins->execute([
                        $tx['donateur_nom'] ?: 'Donateur OSHDI',
                        $tx['donateur_email'] ?: 'contact@oshdi.org',
                        $tx['donateur_telephone'] ?: (defined('PAYMENT_PHONE') ? PAYMENT_PHONE : '+225 07 47 16 64 61'),
                        $tx['item_title'] ?: 'Offrande / Don Général',
                        $tx['montant'],
                        $tx['devise'] ?: 'FCFA',
                        $methodName,
                        $reference,
                        $reqData['message'] ?? 'Soutien aux œuvres divines OSHDI'
                    ]);
                }
            } elseif ($type === 'formation') {
                // Inscription formation
                $userId = $_SESSION['user_id'] ?? 1;
                $ins = $this->pdo->prepare("
                    INSERT INTO inscriptions_formation 
                    (user_id, formation_id, montant_paye, moyen_paiement, reference_transaction, statut)
                    VALUES (?, ?, ?, ?, ?, 'valide')
                ");
                $ins->execute([
                    $userId,
                    $tx['item_id'],
                    $tx['montant'],
                    $methodName,
                    $reference
                ]);
            } elseif ($type === 'espace_partenaire') {
                // Espace partenaire
                $ins = $this->pdo->prepare("
                    INSERT INTO espaces_partenaires 
                    (nom_entreprise, type_activite, description, contact, pack_type, montant_paye, moyen_paiement, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'actif')
                ");
                $ins->execute([
                    $tx['donateur_nom'],
                    'Partenaire Enregistré en Ligne',
                    'Réservation pack partenaire validée via ' . $methodName,
                    $tx['donateur_telephone'],
                    $tx['item_title'] ?: 'Pack Visibilité',
                    $tx['montant'],
                    $methodName
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Erreur confirmation transaction OSHDI: " . $e->getMessage());
            return false;
        }
    }

    /**
     * État de diagnostic de toutes les passerelles pour l'administration
     */
    public function getAllGatewaysStatus() {
        $methods = ['wave', 'orange', 'mtn', 'moov', 'card', 'paypal', 'crypto'];
        $statuses = [];

        foreach ($methods as $m) {
            try {
                $gw = $this->getGateway($m);
                $statuses[$m] = array_merge([
                    'id'   => $m,
                    'name' => $gw->getName(),
                ], $gw->getStatus());
            } catch (Exception $e) {
                $statuses[$m] = [
                    'id' => $m,
                    'name' => ucfirst($m),
                    'is_configured' => false,
                    'mode' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $statuses;
    }
}
