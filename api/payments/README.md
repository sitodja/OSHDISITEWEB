# OSHDI - Documentation des Passerelles de Paiement en Ligne

Ce module fournit une architecture de paiement complète, modulaire et **100% prête pour la mise en ligne (hébergement de production)** pour les 7 réseaux de paiement requis par les donateurs et souscripteurs de l'organisation **OSHDI** (Œuvres Spirituelles et Humanitaires Divines) sur le compte récepteur officiel : **+225 07 47 16 64 61** :

1. **Wave Money** (Côte d'Ivoire, Sénégal, Mali...) - *Compte Marchand : +225 07 47 16 64 61*
2. **Orange Money** (Côte d'Ivoire & Afrique) - *Compte Marchand : +225 07 47 16 64 61 (#144*82#)*
3. **MTN MoMo** (MTN Mobile Money Collections) - *Compte Marchand : +225 07 47 16 64 61 (*133#)*
4. **Moov Money** (Moov Africa Flooz) - *Compte Marchand : +225 07 47 16 64 61 (*155#)*
5. **Carte Bancaire** (Visa, Mastercard, Carte Bleue via Stripe Checkout 3D-Secure)
6. **PayPal** (Règlements internationaux sécurisés en EUR / USD)
7. **Cryptomonnaies** (USDT TRC-20, USDT ERC-20, Bitcoin BTC, Ethereum ETH + Passerelle NOWPayments)

---

## 1. Architecture des Fichiers

```text
c:\wamp64\www\OSHDISITEWEB\
├── .env.example                    <-- Modèle prêt à l'emploi des clés marchandes en production
├── config\
│   ├── config.php                  <-- Constantes globales SITE_PHONE & PAYMENT_PHONE (+225 07 47 16 64 61)
│   ├── payment_config.php          <-- Configuration centralisée (clés API, marchands, adresses)
│   └── db.php                      <-- Connexion BDD et persistance `transactions_paiements`
├── api\
│   ├── initiate_payment.php        <-- Endpoint REST universel d'initialisation de transaction
│   ├── verify_payment.php          <-- Endpoint REST de vérification de statut en temps réel
│   ├── webhook_handler.php         <-- Endpoint récepteur universel des notifications IPN/Webhooks
│   ├── crypto_submit_hash.php      <-- Endpoint d'enregistrement des Hashs blockchain (TxID)
│   ├── payment_process.php         <-- Passerelle universelle avec compatibilité ascendante
│   ├── wave_checkout.php           <-- [DÉDIÉ] API REST Wave Money (+225 07 47 16 64 61)
│   ├── orange_checkout.php         <-- [DÉDIÉ] API REST Orange Money (+225 07 47 16 64 61)
│   ├── mtn_checkout.php            <-- [DÉDIÉ] API REST MTN MoMo (+225 07 47 16 64 61)
│   ├── moov_checkout.php           <-- [DÉDIÉ] API REST Moov Money (+225 07 47 16 64 61)
│   ├── card_checkout.php           <-- [DÉDIÉ] API REST Carte Bancaire Stripe 3D-Secure
│   ├── paypal_checkout.php         <-- [DÉDIÉ] API REST PayPal Checkout v2
│   ├── crypto_checkout.php         <-- [DÉDIÉ] API REST Cryptomonnaies & WhatsApp proof
│   └── payments\
│       ├── PaymentGatewayInterface.php  <-- Interface standardisée de passerelle
│       ├── WaveGateway.php              <-- Adaptateur officiel Wave Checkout Sessions v1
│       ├── OrangeMoneyGateway.php       <-- Adaptateur officiel Orange Developer Webpayment
│       ├── MtnMoMoGateway.php           <-- Adaptateur officiel MTN MoMo Collections
│       ├── MoovMoneyGateway.php         <-- Adaptateur officiel Moov Money Flooz
│       ├── StripeCardGateway.php        <-- Adaptateur officiel Stripe Carte Bancaire 3D-Secure
│       ├── PayPalGateway.php            <-- Adaptateur officiel PayPal REST v2 Checkout
│       ├── CryptoGateway.php            <-- Adaptateur Cryptomonnaies (USDT, BTC, ETH)
│       └── PaymentManager.php           <-- Chef d'orchestre & gestionnaire des transactions BDD
└── admin_paiements.php             <-- Console d'administration, diagnostics et tests en direct
```

---

## 2. Configuration Centralisée (`config/payment_config.php`)

Dès que votre site est hébergé en ligne sur votre nom de domaine (ex: `https://oshdi.org`), ouvrez `config/payment_config.php` et renseignez les identifiants fournis par chaque réseau :

```php
return [
    // 1. WAVE MONEY (https://business.wave.com)
    'wave' => [
        'enabled'        => true,
        'environment'    => 'live', // 'live' ou 'test'
        'api_key'        => 'wave_ci_prod_...', // Votre clé secrète Wave
        'webhook_secret' => 'wave_webhook_secret_...',
    ],

    // 2. ORANGE MONEY (https://developer.orange.com)
    'orange' => [
        'enabled'        => true,
        'environment'    => 'live',
        'client_id'      => 'VOTRE_CLIENT_ID_ORANGE',
        'client_secret'  => 'VOTRE_CLIENT_SECRET_ORANGE',
        'merchant_key'   => 'VOTRE_MERCHANT_KEY_ORANGE',
    ],

    // 3. MTN MOBILE MONEY (https://momodeveloper.mtn.com)
    'mtn' => [
        'enabled'          => true,
        'environment'      => 'production', // 'sandbox' ou 'production'
        'subscription_key' => 'VOTRE_PRIMARY_KEY_MOMO',
        'api_user'         => 'UUID_V4_API_USER',
        'api_key'          => 'CLE_SECRETE_COLLECTION_MOMO',
    ],

    // 4. MOOV MONEY (https://moov-africa.ci)
    'moov' => [
        'enabled'     => true,
        'environment' => 'live',
        'merchant_id' => 'VOTRE_CODE_MARCHAND_MOOV',
        'secret_key'  => 'CLE_SECRETE_MOOV',
    ],

    // 5. CARTE BANCAIRE (https://dashboard.stripe.com/apikeys)
    'card' => [
        'enabled'         => true,
        'environment'     => 'live',
        'publishable_key' => 'pk_live_...',
        'secret_key'      => 'sk_live_...',
        'webhook_secret'  => 'whsec_...',
    ],

    // 6. PAYPAL (https://developer.paypal.com/dashboard)
    'paypal' => [
        'enabled'       => true,
        'environment'   => 'live', // 'sandbox' ou 'live'
        'client_id'     => 'VOTRE_PAYPAL_CLIENT_ID',
        'client_secret' => 'VOTRE_PAYPAL_SECRET_KEY',
    ],

    // 7. CRYPTOMONNAIES (USDT, BTC, ETH)
    'crypto' => [
        'enabled' => true,
        'official_wallets' => [
            'USDT_TRC20' => [
                'name'    => 'Tether USDT (Réseau TRC-20 Tron)',
                'address' => 'TYB76sZkLMN9qwerXop892716382OSHDI992', // Votre adresse USDT TRC20 OSHDI
            ],
            'BTC' => [
                'name'    => 'Bitcoin (BTC)',
                'address' => 'bc1qoshdidivine997238461823791249712', // Votre adresse Bitcoin OSHDI
            ],
            'ETH' => [
                'name'    => 'Ethereum (ETH ERC-20)',
                'address' => '0x3289F4e995E05dBd74eC6dF61872Eb176162d9F1', // Votre adresse Ethereum OSHDI
            ],
        ],
    ]
];
```

*Remarque : Tant que les clés réelles ne sont pas encore saisies, le système bascule automatiquement en **Mode Test & Simulation Démo**, ce qui permet de tester les dons et générer des reçus fiscaux officiels sans bloquer le site.*

---

## 3. Configuration des Webhooks (IPN) sur les Comptes Marchands

Pour que vos encaissements soient validés de manière instantanée et autonome 24h/24, copiez les URLs suivantes dans les consoles respectives :

| Réseau de Paiement | URL du Webhook de Notification (IPN) |
|---|---|
| **Wave Money** | `https://votredomaine.org/api/webhook_handler.php?gateway=wave` |
| **Orange Money** | `https://votredomaine.org/api/webhook_handler.php?gateway=orange` |
| **MTN MoMo** | `https://votredomaine.org/api/webhook_handler.php?gateway=mtn` |
| **Moov Money** | `https://votredomaine.org/api/webhook_handler.php?gateway=moov` |
| **Carte Bancaire (Stripe)** | `https://votredomaine.org/api/webhook_handler.php?gateway=card` |
| **PayPal** | `https://votredomaine.org/api/webhook_handler.php?gateway=paypal` |
| **Cryptomonnaies (NOWPayments)** | `https://votredomaine.org/api/webhook_handler.php?gateway=crypto` |

---

## 4. Endpoints d'API Disponibles

### A. Initialiser un paiement
`POST /api/initiate_payment.php`
```json
{
  "type": "don",
  "amount": 10000,
  "payment_method": "wave",
  "payer_name": "Kouadio Jean",
  "payer_email": "jean.kouadio@gmail.com",
  "payer_phone": "+2250701020304",
  "item_title": "Offrande de Souveraineté de Dieu (CSD)"
}
```

**Réponse JSON :**
```json
{
  "success": true,
  "action": "redirect",
  "redirect_url": "https://pay.wave.com/c/cos-xxxx...",
  "reference": "OSHDI-DON-20261016-849201",
  "gateway_ref": "cos-xxxx",
  "message": "Session de paiement Wave initiée avec succès."
}
```

### B. Vérifier l'état en temps réel
`GET /api/verify_payment.php?ref=OSHDI-DON-20261016-849201`
```json
{
  "success": true,
  "status": "valide",
  "reference": "OSHDI-DON-20261016-849201",
  "amount": 10000,
  "redirect_url": "recu_don.php?ref=OSHDI-DON-20261016-849201"
}
```

### C. Enregistrer un Hash de Transaction Crypto (TxID)
`POST /api/crypto_submit_hash.php`
```json
{
  "reference": "OSHDI-DON-20261016-849201",
  "tx_hash": "e617c0a98b42f77894a87263bba8912c...",
  "crypto_coin": "USDT_TRC20"
}
```

---

## 5. Console de Diagnostic & Administration

Un tableau de bord complet a été développé et est disponible à l'adresse suivante :
👉 `https://votredomaine.org/admin_paiements.php`

Il vous permet de :
- Vérifier l'état opérationnel de chacune des 7 passerelles (Mode Réel vs Mode Test).
- Tester en 1 clic une transaction de 1 000 FCFA sur n'importe quel réseau.
- Consulter le journal exhaustif des transactions (Date, Donateur, Montant, Statut, Reçu).
- Copier-coller facilement les URLs de webhooks pour vos consoles marchandes.
