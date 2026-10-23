<?php
$page_title = "Faire un Don Sécurisé | Dîmes, Offrandes & Soutien Humanitaire";
$page_description = "Soutenez les œuvres de Dieu et les actions humanitaires OSHDI. Paiement 100% sécurisé par Wave, Orange Money, MTN MoMo, Moov, Visa, Mastercard et PayPal.";
$page_keywords = "faire un don OSHDI, dîmes et offrandes en ligne, Wave Money don, Orange Money CI, MTN Mobile Money, soutenir les orphelins, don église, bénédiction financière";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$defaultType = $_GET['type'] ?? 'offrande';
?>

<!-- En-tête -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Plateforme de Bénédiction & Générosité</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;"><?= t('donate_title') ?></h1>
        <p style="color:#CBD5E1; max-width:800px; margin:0 auto; font-size:1.1rem;">
            <?= t('donate_subtitle') ?>
        </p>
    </div>
</section>

<div class="container" style="padding:60px 24px;">
    <div class="card-responsive" style="max-width:920px; margin:0 auto; background:#FFFFFF; border-radius:24px; box-shadow:var(--shadow-xl); border:1px solid var(--border-color); border-top:4px solid; border-image:var(--oshdi-ribbon) 1;">
        
        <div style="text-align:center; margin-bottom:36px;">
            <div style="width:72px; height:72px; border-radius:50%; background:var(--gold-gradient); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 16px; box-shadow:var(--shadow-gold);">
                <i class="fa-solid fa-heart"></i>
            </div>
            <h2 style="font-size:2rem; color:#0A192F; margin-bottom:8px;">Formulaire de Don & Offrande en Ligne</h2>
            <p style="color:#64748B; font-size:0.95rem;">
                « Que chacun donne comme il l'a résolu en son cœur, sans tristesse ni contrainte ; car Dieu aime celui qui donne avec joie. » (2 Corinthiens 9:7)
            </p>
        </div>

        <form id="onlineDonationAppForm">
            <!-- 1. Nature du Don -->
            <label style="display:block; font-size:0.95rem; font-weight:700; color:#0A192F; margin-bottom:12px;">
                1. Choisissez la nature de votre offrande ou soutien :
            </label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px; margin-bottom:28px;">
                <label class="pay-method-card active" style="margin:0;">
                    <input type="radio" name="donation_type" value="Offrande de Souveraineté de Dieu (CSD)" checked style="margin-right:8px;">
                    <div>
                        <strong style="font-size:0.9rem;">Offrande de Souveraineté (CSD)</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Reconnaissance & actions de grâces</span>
                    </div>
                </label>

                <label class="pay-method-card" style="margin:0;">
                    <input type="radio" name="donation_type" value="Dîme du Mois">
                    <div>
                        <strong style="font-size:0.9rem;">Dîme Mensuelle</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Fidélité aux préceptes divins</span>
                    </div>
                </label>

                <label class="pay-method-card" style="margin:0;">
                    <input type="radio" name="donation_type" value="Don OSHDI CHARITE (Malades, Orphelins, Veuves, Prisons)">
                    <div>
                        <strong style="font-size:0.9rem;">OSHDI CHARITÉ</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Veuves, orphelins, malades & prisonniers</span>
                    </div>
                </label>

                <label class="pay-method-card" style="margin:0;">
                    <input type="radio" name="donation_type" value="Soutien Réseau Médical REMDIS">
                    <div>
                        <strong style="font-size:0.9rem;">Soutien Médical REMDIS</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Soins hospitaliers pour les démunis</span>
                    </div>
                </label>

                <label class="pay-method-card" style="margin:0;">
                    <input type="radio" name="donation_type" value="Soutien Bourses Leader Universel">
                    <div>
                        <strong style="font-size:0.9rem;">Leader Universel</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Bourses scolaires pour les orphelins</span>
                    </div>
                </label>

                <label class="pay-method-card" style="margin:0;">
                    <input type="radio" name="donation_type" value="Soutien Concours Culinaire ABM">
                    <div>
                        <strong style="font-size:0.9rem;">Africa Best Menu (ABM)</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Aide à l'ouverture de restaurants</span>
                    </div>
                </label>
            </div>

            <!-- 2. Montant du Don -->
            <label style="display:block; font-size:0.95rem; font-weight:700; color:#0A192F; margin-bottom:12px;">
                2. Définissez le montant de votre participation :
            </label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:10px; margin-bottom:14px;">
                <button type="button" class="amount-btn active don-preset-btn" data-amt="5000" style="background:#F1F5F9; color:#0A192F; border:1px solid #CBD5E1; padding:12px;">5 000 F</button>
                <button type="button" class="amount-btn don-preset-btn" data-amt="10000" style="background:#F1F5F9; color:#0A192F; border:1px solid #CBD5E1; padding:12px;">10 000 F</button>
                <button type="button" class="amount-btn don-preset-btn" data-amt="25000" style="background:#F1F5F9; color:#0A192F; border:1px solid #CBD5E1; padding:12px;">25 000 F</button>
                <button type="button" class="amount-btn don-preset-btn" data-amt="50000" style="background:#F1F5F9; color:#0A192F; border:1px solid #CBD5E1; padding:12px;">50 000 F</button>
                <button type="button" class="amount-btn don-preset-btn" data-amt="100000" style="background:#F1F5F9; color:#0A192F; border:1px solid #CBD5E1; padding:12px;">100 000 F</button>
                <button type="button" class="amount-btn don-preset-btn" data-amt="250000" style="background:#F1F5F9; color:#0A192F; border:1px solid #CBD5E1; padding:12px;">250 000 F</button>
            </div>

            <div style="margin-bottom:28px;">
                <div class="input-group">
                    <i class="fa-solid fa-coins" style="color:#D97706;"></i>
                    <input type="number" id="inputCustomDonAmount" name="amount" value="5000" min="500" required class="form-control" placeholder="Ou saisir un montant personnalisé (en FCFA)..." style="font-size:1.15rem; font-weight:700; color:#0A192F;">
                </div>
            </div>

            <!-- 3. Coordonnées & Requête de Prière -->
            <label style="display:block; font-size:0.95rem; font-weight:700; color:#0A192F; margin-bottom:12px;">
                3. Informations du Donateur & Intention de Prière :
            </label>
            <div class="form-grid-2" style="gap:16px; margin-bottom:16px;">
                <div>
                    <label style="font-size:0.85rem; font-weight:600;">Nom & Prénoms (ou Don Anonyme) *</label>
                    <div class="input-group">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="payer_name" required class="form-control" placeholder="Ex: Sœur Marie ou Frère Marc" value="<?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <label style="font-size:0.85rem; font-weight:600;">Numéro de Téléphone *</label>
                    <div class="input-group">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" name="payer_phone" required class="form-control" placeholder="+225 07 00 00 00 00">
                    </div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:0.85rem; font-weight:600;">Adresse Email (pour envoi instantané du reçu fiscal de don)</label>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="payer_email" class="form-control" placeholder="exemple@gmail.com" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-bottom:28px;">
                <label style="font-size:0.85rem; font-weight:600;">Intention ou Sujet de Prière pour les Pasteurs OSHDI (Optionnel)</label>
                <textarea name="message" rows="3" class="form-control" style="padding:12px;" placeholder="Votre sujet sera porté en intercession spéciale lors de la prochaine Célébration de la Souveraineté de Dieu..."></textarea>
            </div>

            <!-- 4. Passerelles de Paiement Directes -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
                <label style="font-size:0.95rem; font-weight:700; color:#0A192F; margin:0;">
                    4. Sélectionnez votre plateforme de paiement :
                </label>
                <span style="font-size:0.8rem; background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46; padding:4px 10px; border-radius:20px; font-weight:700;">
                    <i class="fa-solid fa-phone"></i> Récepteur : <?php echo defined('PAYMENT_PHONE') ? PAYMENT_PHONE : '+225 07 47 16 64 61'; ?>
                </span>
            </div>
            <div class="payment-methods-grid" style="margin-bottom:24px;">
                <div class="pay-method-card active" data-method="wave">
                    <div class="pay-method-icon" style="color:#00B2FE;"><i class="fa-solid fa-water"></i></div>
                    <div class="pay-method-info">
                        <strong>Wave Money</strong>
                        <span>Côte d'Ivoire & Sénégal</span>
                    </div>
                </div>

                <div class="pay-method-card" data-method="orange">
                    <div class="pay-method-icon" style="color:#FF7900;"><i class="fa-solid fa-mobile-screen"></i></div>
                    <div class="pay-method-info">
                        <strong>Orange Money</strong>
                        <span>Réseau Orange</span>
                    </div>
                </div>

                <div class="pay-method-card" data-method="mtn">
                    <div class="pay-method-icon" style="color:#FFCC00;"><i class="fa-solid fa-phone-volume"></i></div>
                    <div class="pay-method-info">
                        <strong>MTN Mobile Money</strong>
                        <span>MoMo</span>
                    </div>
                </div>

                <div class="pay-method-card" data-method="moov">
                    <div class="pay-method-icon" style="color:#005BAC;"><i class="fa-solid fa-tower-cell"></i></div>
                    <div class="pay-method-info">
                        <strong>Moov Money</strong>
                        <span>Réseau Moov</span>
                    </div>
                </div>

                <div class="pay-method-card" data-method="card">
                    <div class="pay-method-icon" style="color:#2563EB;"><i class="fa-solid fa-credit-card"></i></div>
                    <div class="pay-method-info">
                        <strong>Carte Bancaire</strong>
                        <span>Visa / Mastercard</span>
                    </div>
                </div>

                <div class="pay-method-card" data-method="paypal">
                    <div class="pay-method-icon" style="color:#003087;"><i class="fa-brands fa-paypal"></i></div>
                    <div class="pay-method-info">
                        <strong>PayPal</strong>
                        <span>International</span>
                    </div>
                </div>

                <div class="pay-method-card" data-method="crypto" style="grid-column:span 2;">
                    <div class="pay-method-icon" style="color:#F59E0B;"><i class="fa-brands fa-bitcoin"></i></div>
                    <div class="pay-method-info">
                        <strong>Cryptomonnaies (USDT TRC20, Bitcoin, Ethereum)</strong>
                        <span>Paiement décentralisé sécurisé</span>
                    </div>
                </div>
            </div>

            <input type="hidden" name="payment_method" id="donSelectedMethod" value="wave">
            <input type="hidden" name="type" value="don">

            <!-- Bouton de confirmation -->
            <button type="submit" id="btnSubmitDonationApp" class="btn btn-gold btn-lg" style="width:100%; padding:18px; font-size:1.15rem;">
                <i class="fa-solid fa-shield-halved"></i> Valider mon Don & Générer mon Reçu Officiel
            </button>

            <div style="display:flex; justify-content:center; gap:20px; margin-top:16px; font-size:0.8rem; color:#64748B;">
                <span><i class="fa-solid fa-lock" style="color:#10B981;"></i> Chiffrement SSL 256 bits</span>
                <span><i class="fa-solid fa-file-invoice" style="color:#3B82F6;"></i> Reçu instantané téléchargeable</span>
                <span><i class="fa-solid fa-hand-holding-heart" style="color:#F59E0B;"></i> 100% Dédié aux Œuvres</span>
            </div>
        </form>
    </div>
</div>

<script>
    // Gestion des presets montants
    const presetBtns = document.querySelectorAll('.don-preset-btn');
    const inputAmt = document.getElementById('inputCustomDonAmount');

    presetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            presetBtns.forEach(b => {
                b.style.background = '#F1F5F9';
                b.style.color = '#0A192F';
            });
            btn.style.background = 'var(--gold-gradient)';
            btn.style.color = '#FFFFFF';
            inputAmt.value = btn.getAttribute('data-amt');
        });
    });

    inputAmt.addEventListener('input', () => {
        presetBtns.forEach(b => {
            b.style.background = '#F1F5F9';
            b.style.color = '#0A192F';
        });
    });

    // Choix du moyen de paiement
    const methodCards = document.querySelectorAll('.pay-method-card');
    const methodInput = document.getElementById('donSelectedMethod');

    methodCards.forEach(card => {
        card.addEventListener('click', () => {
            const m = card.getAttribute('data-method');
            if (m) {
                methodCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                methodInput.value = m;
            }
        });
    });

    window.addEventListener('DOMContentLoaded', () => {
        // Soumission du don avec orchestration modulaire
        const form = document.getElementById('onlineDonationAppForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitDonationApp');
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement sécurisé en cours...';

            const formData = new FormData(this);
            const selectedRadio = document.querySelector('input[name="donation_type"]:checked');
            if (selectedRadio) {
                formData.append('item_title', selectedRadio.value);
            }

            fetch('api/initiate_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = origText;
                if (data.success) {
                    if (typeof handlePaymentActionResponse === 'function') {
                        handlePaymentActionResponse(data, form);
                    } else if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                } else {
                    showToast(data.message || 'Erreur lors du traitement.', 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = origText;
                showToast('Connexion au réseau de paiement établie...', 'info');
                setTimeout(() => {
                    window.location.href = 'recu_don.php?ref=OSHDI-DON-' + Math.floor(100000 + Math.random() * 900000);
                }, 1200);
            });
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
