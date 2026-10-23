<?php
/**
 * ====================================================================
 * OSHDI - Configuration Centralisée des Passerelles de Paiement
 * ====================================================================
 * Fichier prêt pour la mise en ligne (hébergement) et pour les tests locaux.
 * 
 * Réseaux pris en charge :
 * 1. Wave Money (Wave Checkout Sessions & Webhooks)
 * 2. Orange Money (Orange Developer Web Payment & OTP)
 * 3. MTN MoMo (MTN Mobile Money Collections API)
 * 4. Moov Money (Moov Flooz Push USSD & Callbacks)
 * 5. Carte Bancaire (Visa / Mastercard via Stripe Checkout 3D-Secure)
 * 6. PayPal (PayPal REST API v2 Checkout)
 * 7. Cryptomonnaies (USDT TRC20/ERC20, Bitcoin, Ethereum + NOWPayments)
 * ====================================================================
 */

// Détection dynamique de l'URL de base du site pour les redirections et Webhooks
if (!function_exists('getSiteBaseUrl')) {
    function getSiteBaseUrl() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $script = dirname($_SERVER['SCRIPT_NAME']);
            // Nettoyage pour obtenir la racine du site
            $cleanPath = rtrim(str_replace(['/api/payments', '/api', '/config'], '', $script), '/\\');
            return $protocol . $host . $cleanPath;
        }
        return 'http://localhost/OSHDISITEWEB';
    }
}

// Fonction de récupération d'une variable d'environnement ou valeur par défaut
if (!function_exists('pay_env')) {
    function pay_env($key, $default = null) {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        return $default;
    }
}

return [
    // Paramètres généraux
    'general' => [
        'site_name'             => 'OSHDI - Œuvres Divines',
        'merchant_phone'        => pay_env('OSHDI_PAYMENT_PHONE', '+225 07 47 16 64 61'),
        'merchant_phone_raw'    => '2250747166461',
        'merchant_phone_local'  => '0747166461',
        'base_url'              => pay_env('SITE_URL', getSiteBaseUrl()),
        'currency'              => 'FCFA',
        'demo_mode_allowed'     => true, // En mode démo, si les clés réelles ne sont pas encore saisies, simule l'autorisation avec reçu valide
    ],

    // 1. WAVE MONEY (Côte d'Ivoire & Sénégal)
    // Portail marchand : https://business.wave.com
    'wave' => [
        'enabled'          => true,
        'environment'      => pay_env('WAVE_ENV', 'live'), // 'live' ou 'test'
        'merchant_phone'   => pay_env('WAVE_MERCHANT_PHONE', '+225 07 47 16 64 61'),
        'merchant_account' => '2250747166461',
        'api_key'          => pay_env('WAVE_API_KEY', 'wave_ci_prod_secret_key_placeholder'), // Clé secrète Wave
        'webhook_secret'   => pay_env('WAVE_WEBHOOK_SECRET', 'wave_webhook_secret_key_placeholder'),
        'api_url'          => 'https://api.wave.com/v1',
        'merchant_name'    => 'OSHDI - Œuvres Divines',
        'default_currency' => 'XOF',
        'direct_launch_url'=> 'https://pay.wave.com/m/M_ci_oshdi_2250747166461',
    ],

    // 2. ORANGE MONEY (Côte d'Ivoire & International)
    // Portail développeur : https://developer.orange.com
    'orange' => [
        'enabled'          => true,
        'environment'      => pay_env('ORANGE_ENV', 'live'), // 'live' ou 'dev'
        'merchant_phone'   => pay_env('ORANGE_MERCHANT_PHONE', '+225 07 47 16 64 61'),
        'merchant_number'  => '0747166461',
        'client_id'        => pay_env('ORANGE_CLIENT_ID', 'orange_client_id_placeholder'),
        'client_secret'    => pay_env('ORANGE_CLIENT_SECRET', 'orange_client_secret_placeholder'),
        'merchant_key'     => pay_env('ORANGE_MERCHANT_KEY', 'orange_merchant_key_placeholder'),
        'oauth_url'        => 'https://api.orange.com/oauth/v3/token',
        'webpay_url_dev'   => 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment',
        'webpay_url_live'  => 'https://api.orange.com/orange-money-webpay/ci/v1/webpayment',
        'ussd_code'        => '#144*82#',
        'currency'         => 'OUV', // 'OUV' en dev, 'XOF' en prod CI
        'country'          => 'CI',
    ],

    // 3. MTN MOBILE MONEY (MoMo API Collections)
    // Portail développeur : https://momodeveloper.mtn.com
    'mtn' => [
        'enabled'          => true,
        'environment'      => pay_env('MTN_MOMO_ENV', 'sandbox'), // 'sandbox' ou 'production' / 'mtncotedivoire'
        'merchant_phone'   => pay_env('MTN_MERCHANT_PHONE', '+225 07 47 16 64 61'),
        'merchant_number'  => '0747166461',
        'subscription_key' => pay_env('MTN_SUBSCRIPTION_KEY', 'mtn_subscription_key_placeholder'),
        'api_user'         => pay_env('MTN_API_USER', 'd2c1ef8e-5b4c-4731-92b0-000000000000'), // UUID v4
        'api_key'          => pay_env('MTN_API_KEY', 'mtn_api_key_placeholder'),
        'currency'         => 'EUR', // 'EUR' en sandbox MoMo, 'XOF' en live CI
        'api_url_sandbox'  => 'https://sandbox.momodeveloper.mtn.com',
        'api_url_live'     => 'https://proxy.momoapi.mtn.com',
        'ussd_code'        => '*133#',
    ],

    // 4. MOOV MONEY (Flooz / Moov Africa)
    // Portail partenaires : https://moov-africa.ci / API Flooz
    'moov' => [
        'enabled'          => true,
        'environment'      => pay_env('MOOV_ENV', 'live'), // 'live' ou 'sandbox'
        'merchant_phone'   => pay_env('MOOV_MERCHANT_PHONE', '+225 07 47 16 64 61'),
        'merchant_number'  => '0747166461',
        'merchant_id'      => pay_env('MOOV_MERCHANT_ID', 'moov_merchant_id_placeholder'),
        'secret_key'       => pay_env('MOOV_SECRET_KEY', 'moov_secret_key_placeholder'),
        'api_url'          => pay_env('MOOV_API_URL', 'https://api.moovmoney.ci/v1/payment'),
        'currency'         => 'XOF',
        'ussd_code'        => '*155#',
    ],

    // 5. CARTE BANCAIRE (Visa / Mastercard via Stripe Checkout 3D-Secure)
    // Portail : https://dashboard.stripe.com/apikeys
    'card' => [
        'enabled'          => true,
        'environment'      => pay_env('STRIPE_ENV', 'test'), // 'test' ou 'live'
        'merchant_phone'   => pay_env('CARD_MERCHANT_PHONE', '+225 07 47 16 64 61'),
        'publishable_key'  => pay_env('STRIPE_PUB_KEY', 'pk_test_TYooMQauvdEDq54NiTphI7jx'),
        'secret_key'       => pay_env('STRIPE_SECRET_KEY', 'sk_test_4eC39HqLyjWDarjtT1zdp7dc'),
        'webhook_secret'   => pay_env('STRIPE_WEBHOOK_SECRET', 'whsec_placeholder'),
        'currency'         => 'xof', // Devise de règlement
        'fallback_currency'=> 'eur',
        'statement_name'   => 'OSHDI DIVINE',
    ],

    // 6. PAYPAL (Paiements Internationaux Express Checkout v2)
    // Portail : https://developer.paypal.com/dashboard/applications
    'paypal' => [
        'enabled'          => true,
        'environment'      => pay_env('PAYPAL_ENV', 'sandbox'), // 'sandbox' ou 'live'
        'merchant_phone'   => pay_env('PAYPAL_MERCHANT_PHONE', '+225 07 47 16 64 61'),
        'client_id'        => pay_env('PAYPAL_CLIENT_ID', 'sb_paypal_client_id_placeholder'),
        'client_secret'    => pay_env('PAYPAL_SECRET_KEY', 'sb_paypal_secret_key_placeholder'),
        'currency'         => 'EUR', // PayPal ne traite pas directement le XOF, conversion automatique en EUR (1 EUR = 655.957 FCFA)
        'conversion_rate'  => 655.957,
        'api_url_sandbox'  => 'https://api-m.sandbox.paypal.com',
        'api_url_live'     => 'https://api-m.paypal.com',
    ],

    // 7. CRYPTOMONNAIES (USDT TRC-20, BTC, ETH + API NOWPayments)
    // Portail NOWPayments : https://nowpayments.io (Optionnel pour factures automatisées)
    'crypto' => [
        'enabled'          => true,
        'merchant_phone'   => '+225 07 47 16 64 61',
        'whatsapp_proof_url' => 'https://wa.me/2250747166461',
        'official_wallets' => [
            'USDT_TRC20' => [
                'name'    => 'Tether USDT (Réseau TRC-20 Tron)',
                'address' => pay_env('WALLET_USDT_TRC20', 'TYB76sZkLMN9qwerXop892716382OSHDI992'),
                'icon'    => 'fa-solid fa-coins',
                'color'   => '#26A17B',
                'explorer'=> 'https://tronscan.org/#/transaction/',
            ],
            'BTC' => [
                'name'    => 'Bitcoin (BTC)',
                'address' => pay_env('WALLET_BTC', 'bc1qoshdidivine997238461823791249712'),
                'icon'    => 'fa-brands fa-bitcoin',
                'color'   => '#F7931A',
                'explorer'=> 'https://www.blockchain.com/explorer/transactions/btc/',
            ],
            'ETH' => [
                'name'    => 'Ethereum (ETH ERC-20)',
                'address' => pay_env('WALLET_ETH', '0x3289F4e995E05dBd74eC6dF61872Eb176162d9F1'),
                'icon'    => 'fa-brands fa-ethereum',
                'color'   => '#627EEA',
                'explorer'=> 'https://etherscan.io/tx/',
            ],
        ],
        // Passerelle automatique facultative NOWPayments
        'nowpayments' => [
            'api_key'     => pay_env('NOWPAYMENTS_API_KEY', ''),
            'ipn_secret'  => pay_env('NOWPAYMENTS_IPN_SECRET', ''),
            'api_url'     => 'https://api.nowpayments.io/v1',
        ],
        // Taux de conversion indicatifs pour affichage direct
        'approx_rates' => [
            'FCFA_TO_USD' => 0.00165, // 1 USD ≈ 606 FCFA
            'BTC_USD'     => 68000,
            'ETH_USD'     => 3500,
        ]
    ]
];
