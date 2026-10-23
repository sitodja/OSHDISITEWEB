<?php
$page_title = "Console d'Administration & Gestion des Flux Monétiques";
$noindex = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/api/payments/PaymentManager.php';

$manager = PaymentManager::getInstance();
$gatewaysStatus = $manager->getAllGatewaysStatus();
$config = $manager->getConfig();
$baseUrl = rtrim($config['general']['base_url'], '/');

// Récupération des transactions récentes
global $pdo;
$transactions = [];
try {
    $stmt = $pdo->query("SELECT * FROM transactions_paiements ORDER BY date_creation DESC LIMIT 50");
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    $transactions = [];
}
?>

<div style="background: linear-gradient(135deg, #0A192F 0%, #1E293B 100%); color:#FFFFFF; padding:45px 20px;">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">
                    <i class="fa-solid fa-server"></i> Console des Passerelles En Ligne
                </span>
                <h1 style="color:#FFFFFF; font-size:2.2rem; margin:10px 0 6px;">État des 7 Réseaux de Paiement OSHDI</h1>
                <p style="color:#94A3B8; font-size:0.95rem; margin:0 0 10px;">
                    Prêt pour l'hébergement de production. Vérifiez les clés API, testez les flux et consultez les webhooks.
                </p>
                <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.4); color:#34D399; padding:6px 14px; border-radius:20px; font-weight:700; font-size:0.85rem;">
                    <i class="fa-solid fa-phone-volume"></i> Numéro & Compte Marchand Récepteur Central : <span style="color:#FFFFFF;">+225 07 47 16 64 61</span>
                </div>
            </div>
            <div>
                <a href="#guideMiseEnLigne" class="btn btn-gold" style="padding:12px 20px;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Guide d'Activation en Ligne
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding:40px 20px;">

    <!-- Alert Information Hébergement -->
    <div style="background:#EFF6FF; border:1px solid #BFDBFE; border-left:5px solid #3B82F6; border-radius:12px; padding:18px 24px; margin-bottom:32px;">
        <div style="display:flex; gap:16px; align-items:flex-start;">
            <i class="fa-solid fa-circle-info" style="font-size:1.5rem; color:#3B82F6; margin-top:3px;"></i>
            <div>
                <h4 style="margin:0 0 6px; color:#1E3A8A; font-size:1.05rem;">Prêt pour l'Hébergement Web & Comptes Marchands (+225 07 47 16 64 61)</h4>
                <p style="margin:0; color:#1E40AF; font-size:0.9rem; line-height:1.5;">
                    Les 7 API de paiement en ligne sont opérationnelles et configurées pour le compte officiel OSHDI <strong>+225 07 47 16 64 61</strong>. Dès que le site est hébergé en ligne sur votre nom de domaine (HTTPS), renseignez simplement vos clés de production dans <code>config/payment_config.php</code> ou via votre fichier <code>.env</code>. Chaque réseau dispose également d'un endpoint REST direct (ex: <code>api/wave_checkout.php</code>) pour les intégrations mobiles ou partenaires.
                </p>
            </div>
        </div>
    </div>

    <!-- Grille des 7 Passerelles -->
    <h2 style="font-size:1.4rem; color:#0A192F; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-network-wired" style="color:#F59E0B;"></i> Réseaux de Paiement Configurés
    </h2>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 290px), 1fr)); gap:20px; margin-bottom:45px;">
        <?php
        $networkDetails = [
            'wave' => [
                'icon' => 'fa-solid fa-water', 'color' => '#00B2FE', 'badge' => 'Wave CI / SN',
                'account' => '+225 07 47 16 64 61',
                'endpoint' => $baseUrl . '/api/wave_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=wave',
                'desc' => 'API Wave Checkout Sessions v1 & Webhooks de confirmation instantanée.'
            ],
            'orange' => [
                'icon' => 'fa-solid fa-mobile-screen', 'color' => '#FF7900', 'badge' => 'Orange Money CI',
                'account' => '+225 07 47 16 64 61 (USSD: #144*82#)',
                'endpoint' => $baseUrl . '/api/orange_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=orange',
                'desc' => 'API Orange Developer Webpayment OAuth2 & Notification USSD OTP.'
            ],
            'mtn' => [
                'icon' => 'fa-solid fa-phone-volume', 'color' => '#EAB308', 'badge' => 'MTN MoMo Collections',
                'account' => '+225 07 47 16 64 61 (USSD: *133#)',
                'endpoint' => $baseUrl . '/api/mtn_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=mtn',
                'desc' => 'API MTN Mobile Money Collections requestToPay avec Push USSD automatique.'
            ],
            'moov' => [
                'icon' => 'fa-solid fa-tower-cell', 'color' => '#0284C7', 'badge' => 'Moov Africa Flooz',
                'account' => '+225 07 47 16 64 61 (USSD: *155#)',
                'endpoint' => $baseUrl . '/api/moov_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=moov',
                'desc' => 'Passerelle Marchand Moov Money avec validation par code secret *155#.'
            ],
            'card' => [
                'icon' => 'fa-solid fa-credit-card', 'color' => '#2563EB', 'badge' => 'Visa / Mastercard',
                'account' => 'Contact Support : +225 07 47 16 64 61',
                'endpoint' => $baseUrl . '/api/card_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=card',
                'desc' => 'Stripe Checkout Sessions sécurisé 3D-Secure pour toutes cartes bancaires.'
            ],
            'paypal' => [
                'icon' => 'fa-brands fa-paypal', 'color' => '#003087', 'badge' => 'PayPal International',
                'account' => 'Contact Support : +225 07 47 16 64 61',
                'endpoint' => $baseUrl . '/api/paypal_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=paypal',
                'desc' => 'API PayPal v2 Orders Create & Capture avec conversion automatique FCFA/EUR.'
            ],
            'crypto' => [
                'icon' => 'fa-brands fa-bitcoin', 'color' => '#F59E0B', 'badge' => 'USDT, BTC, ETH',
                'account' => 'WhatsApp TxID : +225 07 47 16 64 61',
                'endpoint' => $baseUrl . '/api/crypto_checkout.php',
                'webhook' => $baseUrl . '/api/webhook_handler.php?gateway=crypto',
                'desc' => 'Wallets certifiés OSHDI, QR codes HD, conversion live et soumission TxID hash.'
            ],
        ];

        foreach ($gatewaysStatus as $key => $gw):
            $meta = $networkDetails[$key] ?? [];
            $isConfigured = !empty($gw['is_configured']);
        ?>
            <div style="background:#FFFFFF; border-radius:16px; border:1px solid #E2E8F0; padding:24px; box-shadow:0 4px 15px rgba(0,0,0,0.04); display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:48px; height:48px; border-radius:12px; background:<?= $meta['color'] ?>15; color:<?= $meta['color'] ?>; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                                <i class="<?= $meta['icon'] ?>"></i>
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:1.1rem; color:#0A192F;"><?= htmlspecialchars($gw['name']) ?></h3>
                                <span style="font-size:0.75rem; color:#64748B; font-weight:600;"><?= $meta['badge'] ?></span>
                            </div>
                        </div>

                        <?php if ($isConfigured): ?>
                            <span style="background:#DCFCE7; color:#15803D; font-size:0.75rem; font-weight:700; padding:4px 10px; border-radius:20px;">
                                <i class="fa-solid fa-circle-check"></i> Prêt
                            </span>
                        <?php else: ?>
                            <span style="background:#FEF3C7; color:#B45309; font-size:0.75rem; font-weight:700; padding:4px 10px; border-radius:20px;">
                                <i class="fa-solid fa-flask"></i> Mode Test
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="margin-bottom:12px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:8px 12px; display:flex; align-items:center; gap:8px; font-size:0.8rem;">
                        <i class="fa-solid fa-mobile-screen" style="color:#F59E0B;"></i>
                        <span style="color:#64748B;">Compte / Ligne OSHDI :</span>
                        <strong style="color:#0A192F;"><?= htmlspecialchars($meta['account']) ?></strong>
                    </div>

                    <p style="color:#64748B; font-size:0.85rem; line-height:1.45; margin-bottom:14px;">
                        <?= $meta['desc'] ?>
                    </p>

                    <!-- Endpoint REST dédié -->
                    <div style="background:#F1F5F9; border:1px solid #E2E8F0; border-radius:8px; padding:8px 12px; margin-bottom:10px;">
                        <span style="font-size:0.68rem; text-transform:uppercase; font-weight:700; color:#64748B; display:block; margin-bottom:2px;">
                            Endpoint API Direct :
                        </span>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <code style="font-size:0.72rem; color:#0369A1; word-break:break-all;"><?= htmlspecialchars($meta['endpoint']) ?></code>
                            <button type="button" class="btn btn-outline" style="padding:2px 6px; font-size:0.7rem;" onclick="copyToClipboard('<?= htmlspecialchars($meta['endpoint']) ?>')">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Webhook IPN -->
                    <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:8px 12px; margin-bottom:16px;">
                        <span style="font-size:0.68rem; text-transform:uppercase; font-weight:700; color:#94A3B8; display:block; margin-bottom:2px;">
                            URL Webhook (IPN) à copier chez le marchand :
                        </span>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <code style="font-size:0.72rem; color:#0A192F; word-break:break-all;"><?= htmlspecialchars($meta['webhook']) ?></code>
                            <button type="button" class="btn btn-outline" style="padding:2px 6px; font-size:0.7rem;" onclick="copyToClipboard('<?= htmlspecialchars($meta['webhook']) ?>')">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" class="btn btn-primary" style="flex:1; padding:8px 12px; font-size:0.85rem;" onclick="testGatewayPayment('<?= $key ?>')">
                        <i class="fa-solid fa-play"></i> Tester (1 000 F)
                    </button>
                    <?php if (!empty($gw['merchant_portal'])): ?>
                        <a href="<?= htmlspecialchars($gw['merchant_portal']) ?>" target="_blank" class="btn btn-outline" style="padding:8px 12px; font-size:0.85rem;" title="Ouvrir le portail marchand">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Section Historique des Transactions Récentes -->
    <div style="background:#FFFFFF; border-radius:16px; border:1px solid #E2E8F0; padding:28px; box-shadow:0 4px 20px rgba(0,0,0,0.04); margin-bottom:40px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
            <div>
                <h3 style="margin:0; font-size:1.25rem; color:#0A192F;">
                    <i class="fa-solid fa-list-check" style="color:#F59E0B;"></i> Journal des Transactions de Paiement
                </h3>
                <span style="font-size:0.8rem; color:#64748B;">Suivi direct des dons, formations et souscriptions</span>
            </div>
            <a href="admin_paiements.php" class="btn btn-outline" style="font-size:0.85rem; padding:6px 14px;">
                <i class="fa-solid fa-rotate-right"></i> Actualiser
            </a>
        </div>

        <?php if (empty($transactions)): ?>
            <p style="text-align:center; color:#94A3B8; padding:30px 0;">Aucune transaction enregistrée pour le moment. Effectuez un test ci-dessus.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                    <thead>
                        <tr style="background:#F8FAFC; border-bottom:2px solid #E2E8F0; text-align:left; color:#475569;">
                            <th style="padding:12px 14px;">Référence</th>
                            <th style="padding:12px 14px;">Date</th>
                            <th style="padding:12px 14px;">Payeur</th>
                            <th style="padding:12px 14px;">Objet</th>
                            <th style="padding:12px 14px;">Réseau</th>
                            <th style="padding:12px 14px;">Montant</th>
                            <th style="padding:12px 14px;">Statut</th>
                            <th style="padding:12px 14px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr style="border-bottom:1px solid #F1F5F9;">
                                <td style="padding:12px 14px; font-weight:700; color:#0A192F;">
                                    <?= htmlspecialchars($tx['reference']) ?>
                                </td>
                                <td style="padding:12px 14px; color:#64748B;">
                                    <?= date('d/m/Y H:i', strtotime($tx['date_creation'])) ?>
                                </td>
                                <td style="padding:12px 14px;">
                                    <strong><?= htmlspecialchars($tx['donateur_nom'] ?: 'Anonyme') ?></strong><br>
                                    <span style="font-size:0.75rem; color:#64748B;"><?= htmlspecialchars($tx['donateur_telephone']) ?></span>
                                </td>
                                <td style="padding:12px 14px; color:#475569;">
                                    <?= htmlspecialchars($tx['item_title'] ?: ucfirst($tx['type_commande'])) ?>
                                </td>
                                <td style="padding:12px 14px; text-transform:uppercase; font-weight:600;">
                                    <span style="background:#F1F5F9; padding:3px 8px; border-radius:6px;">
                                        <?= htmlspecialchars($tx['moyen_paiement']) ?>
                                    </span>
                                </td>
                                <td style="padding:12px 14px; font-weight:700; color:#059669;">
                                    <?= number_format($tx['montant'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td style="padding:12px 14px;">
                                    <?php if ($tx['statut'] === 'valide'): ?>
                                        <span style="background:#DCFCE7; color:#15803D; padding:3px 10px; border-radius:12px; font-weight:700; font-size:0.75rem;">
                                            <i class="fa-solid fa-check"></i> Validé
                                        </span>
                                    <?php else: ?>
                                        <span style="background:#FEF3C7; color:#B45309; padding:3px 10px; border-radius:12px; font-weight:700; font-size:0.75rem;">
                                            <i class="fa-solid fa-clock"></i> En attente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 14px;">
                                    <a href="recu_don.php?ref=<?= urlencode($tx['reference']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 10px; font-size:0.75rem;">
                                        <i class="fa-solid fa-receipt"></i> Reçu
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Guide d'activation en ligne -->
    <div id="guideMiseEnLigne" style="background:#FFFFFF; border-radius:16px; border:1px solid #E2E8F0; padding:32px; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
        <h3 style="margin-top:0; font-size:1.35rem; color:#0A192F; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-book-bookmark" style="color:#F59E0B;"></i> Guide de Mise en Ligne : Activation des Comptes Marchands
        </h3>
        <p style="color:#64748B; font-size:0.95rem; line-height:1.6;">
            Dès que le site est hébergé en ligne (ex: sur <code>https://oshdi.org</code>), suivez ces 3 étapes simples pour activer les encaissements directs vers les comptes bancaires et mobiles de l'organisation :
        </p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-top:20px;">
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:20px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#0A192F; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:12px;">1</div>
                <h4 style="margin:0 0 8px; color:#0A192F; font-size:1rem;">Créer vos comptes marchands</h4>
                <p style="font-size:0.85rem; color:#64748B; margin:0; line-height:1.5;">
                    Ouvrez vos comptes marchands officiels auprès de Wave Business, Orange Money Developer, MTN MoMo Developer, Moov, Stripe et PayPal.
                </p>
            </div>

            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:20px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#F59E0B; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:12px;">2</div>
                <h4 style="margin:0 0 8px; color:#0A192F; font-size:1rem;">Coller vos Clés API</h4>
                <p style="font-size:0.85rem; color:#64748B; margin:0; line-height:1.5;">
                    Dans le fichier <code style="color:#D97706;">config/payment_config.php</code>, remplacez les valeurs <code>placeholder</code> par vos clés secrètes fournies par chaque réseau.
                </p>
            </div>

            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:20px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#10B981; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:12px;">3</div>
                <h4 style="margin:0 0 8px; color:#0A192F; font-size:1rem;">Configurer les Webhooks</h4>
                <p style="font-size:0.85rem; color:#64748B; margin:0; line-height:1.5;">
                    Dans chaque portail marchand, copiez l'URL Webhook correspondante indiquée ci-dessus. Vos transactions seront ainsi validées instantanément 24h/24.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('URL du Webhook copiée dans le presse-papier !', 'success');
    }).catch(() => {
        prompt('Copiez l\'URL ci-dessous :', text);
    });
}

function testGatewayPayment(method) {
    showToast('Initialisation du test de transaction pour ' + method.toUpperCase() + '...', 'info');

    const formData = new FormData();
    formData.append('type', 'don');
    formData.append('amount', '1000');
    formData.append('payment_method', method);
    formData.append('payer_name', 'Test Automatisé OSHDI');
    formData.append('payer_email', 'contact@oshdi.org');
    formData.append('payer_phone', '+225 07 47 16 64 61');
    formData.append('item_title', 'Don Test Diagnostics API (' + method.toUpperCase() + ')');

    fetch('api/initiate_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Succès ! Réf: ' + data.reference + ' (' + (data.message || 'Prêt') + ')', 'success');
            setTimeout(() => {
                if (data.action === 'redirect' && data.redirect_url) {
                    window.open(data.redirect_url, '_blank');
                } else {
                    location.reload();
                }
            }, 1200);
        } else {
            showToast(data.message || 'Erreur lors du test.', 'error');
        }
    })
    .catch(err => {
        showToast('Erreur de communication : ' + err.message, 'error');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
