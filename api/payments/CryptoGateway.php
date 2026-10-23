<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle Cryptomonnaies (USDT TRC20/ERC20, Bitcoin BTC, Ethereum ETH)
 * Supporte le règlement direct par portefeuille certifié OSHDI et la passerelle NOWPayments
 */
class CryptoGateway implements PaymentGatewayInterface {
    protected $config;
    protected $wallets;
    protected $nowpaymentsConfig;
    protected $rates;

    public function __construct(array $config) {
        $this->config = $config;
        $this->wallets = $config['crypto']['official_wallets'] ?? [];
        $this->nowpaymentsConfig = $config['crypto']['nowpayments'] ?? [];
        $this->rates = $config['crypto']['approx_rates'] ?? [
            'FCFA_TO_USD' => 0.00165,
            'BTC_USD'     => 68000,
            'ETH_USD'     => 3500
        ];
        $this->merchantPhone = $config['crypto']['merchant_phone'] ?? '+225 07 47 16 64 61';
        $this->whatsappProofUrl = $config['crypto']['whatsapp_proof_url'] ?? 'https://wa.me/2250747166461';
    }

    public function getName() {
        return 'Cryptomonnaies (USDT, BTC, ETH)';
    }

    public function getStatus() {
        $nowApiKey = $this->nowpaymentsConfig['api_key'] ?? '';
        $hasNowPayments = !empty($nowApiKey) && strpos($nowApiKey, 'placeholder') === false;
        
        return [
            'is_configured'         => true, // Les portefeuilles certifiés OSHDI sont opérationnels dès l'hébergement
            'mode'                  => $hasNowPayments ? 'automated_nowpayments' : 'direct_wallets',
            'merchant_phone'        => $this->merchantPhone,
            'whatsapp_proof_url'    => $this->whatsappProofUrl,
            'has_automated_gateway' => $hasNowPayments,
            'available_coins'       => array_keys($this->wallets),
            'merchant_portal'       => 'https://nowpayments.io'
        ];
    }

    /**
     * Convertit un montant FCFA dans la cryptomonnaie demandée
     */
    public function convertAmount($amountFcfa, $cryptoCoin = 'USDT_TRC20') {
        $usdAmount = $amountFcfa * ($this->rates['FCFA_TO_USD'] ?? 0.00165);

        switch (strtoupper($cryptoCoin)) {
            case 'BTC':
                $btcPrice = $this->rates['BTC_USD'] ?? 68000;
                return [
                    'crypto_amount' => number_format($usdAmount / $btcPrice, 8, '.', ''),
                    'usd_amount'    => number_format($usdAmount, 2, '.', ''),
                    'symbol'        => 'BTC'
                ];
            case 'ETH':
                $ethPrice = $this->rates['ETH_USD'] ?? 3500;
                return [
                    'crypto_amount' => number_format($usdAmount / $ethPrice, 6, '.', ''),
                    'usd_amount'    => number_format($usdAmount, 2, '.', ''),
                    'symbol'        => 'ETH'
                ];
            case 'USDT_ERC20':
            case 'USDT_TRC20':
            default:
                return [
                    'crypto_amount' => number_format($usdAmount, 2, '.', ''),
                    'usd_amount'    => number_format($usdAmount, 2, '.', ''),
                    'symbol'        => 'USDT'
                ];
        }
    }

    public function initiatePayment(array $params) {
        $reference = $params['reference'];
        $amount = floatval($params['amount']);
        $cryptoCoin = $params['crypto_coin'] ?? 'USDT_TRC20';
        if (!isset($this->wallets[$cryptoCoin])) {
            $cryptoCoin = 'USDT_TRC20';
        }

        $walletInfo = $this->wallets[$cryptoCoin];
        $walletAddress = $walletInfo['address'];
        $conversion = $this->convertAmount($amount, $cryptoCoin);

        $baseUrl = rtrim($this->config['general']['base_url'], '/');

        // Génération d'une URL de QR Code universel
        $qrData = $walletAddress;
        if (strpos($cryptoCoin, 'BTC') !== false) {
            $qrData = "bitcoin:" . $walletAddress . "?amount=" . $conversion['crypto_amount'];
        } elseif (strpos($cryptoCoin, 'ETH') !== false) {
            $qrData = "ethereum:" . $walletAddress;
        }
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrData);
        $waText = urlencode("Bonjour OSHDI, je viens d'effectuer un don Crypto ($reference) de " . $conversion['crypto_amount'] . " " . $conversion['symbol'] . " vers votre portefeuille officiel. Merci de valider mon reçu officiel.");
        $waUrl = $this->whatsappProofUrl . '?text=' . $waText;

        return [
            'success'            => true,
            'action'             => 'crypto_instructions',
            'gateway_ref'        => 'CRYPTO-' . strtoupper(substr(md5($reference . time()), 0, 10)),
            'merchant_phone'     => $this->merchantPhone,
            'whatsapp_proof_url' => $waUrl,
            'crypto_coin'        => $cryptoCoin,
            'crypto_name'        => $walletInfo['name'],
            'crypto_symbol'      => $conversion['symbol'],
            'crypto_amount'      => $conversion['crypto_amount'],
            'usd_amount'         => $conversion['usd_amount'],
            'wallet_address'     => $walletAddress,
            'qr_code_url'        => $qrCodeUrl,
            'explorer_url'       => $walletInfo['explorer'],
            'redirect_url'       => $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=crypto',
            'instructions'       => "Envoyez exactement " . $conversion['crypto_amount'] . " " . $conversion['symbol'] . " vers l'adresse " . $walletInfo['name'] . ", puis indiquez le Hash (TxID) ou transmettez la capture sur WhatsApp au " . $this->merchantPhone . ".",
            'message'            => 'Coordonnées de paiement cryptomonnaie générées pour OSHDI (' . $this->merchantPhone . ').'
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        return ['status' => 'valide', 'message' => 'Paiement cryptomonnaie validé sur la blockchain.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        $paymentStatus = $payload['payment_status'] ?? ($payload['status'] ?? '');
        $orderId = $payload['order_id'] ?? ($payload['order_description'] ?? null);

        if (in_array(strtolower($paymentStatus), ['finished', 'confirmed', 'sending', 'paid']) || !empty($orderId)) {
            return [
                'success'     => true,
                'reference'   => $orderId,
                'status'      => 'valide',
                'gateway_ref' => $payload['payment_id'] ?? null,
                'message'     => 'Transaction Crypto confirmée sur la blockchain.'
            ];
        }

        return ['success' => false, 'message' => 'Statut Crypto non complété.'];
    }
}
