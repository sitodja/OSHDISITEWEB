<!-- MODAL DE PAIEMENT UNIVERSEL MULTI-CANAL OSHDI -->
<div id="universalPaymentModal" class="modal-overlay">
    <div class="payment-modal">
        <div class="modal-header">
            <h3 id="modalPaymentTitle"><i class="fa-solid fa-lock" style="color:#F59E0B;"></i> Passerelle Sécurisée OSHDI</h3>
            <button type="button" class="modal-close-btn">&times;</button>
        </div>

        <form id="universalPaymentForm" class="modal-body">
            <!-- Champs cachés de transaction -->
            <input type="hidden" name="type" id="payInputType" value="don">
            <input type="hidden" name="item_id" id="payInputItemId" value="0">
            <input type="hidden" name="item_title" id="payInputItemTitle" value="">
            <input type="hidden" name="amount" id="payInputAmount" value="5000">
            <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="wave">

            <!-- Résumé du paiement -->
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:16px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700;">Objet</span>
                    <strong id="modalPaymentItemName" style="display:block; font-size:1rem; color:#0A192F;">Contribution OSHDI</strong>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700;">Montant Total</span>
                    <strong id="modalPaymentAmount" style="display:block; font-size:1.35rem; color:#059669; font-family:'Outfit', sans-serif;">5 000 FCFA</strong>
                </div>
            </div>

            <!-- Coordonnées du payeur -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Votre Nom Complet / Raison Sociale</label>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="payer_name" required class="form-control" placeholder="Ex: Jean Kouadio" value="<?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Adresse Email pour Reçu Officiel</label>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="payer_email" required class="form-control" placeholder="exemple@gmail.com" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
                </div>
            </div>

            <!-- Choix du moyen de paiement (Local & International) -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-top:20px; margin-bottom:8px;">
                <label style="font-size:0.85rem; font-weight:700; color:#0A192F; margin:0;">
                    Sélectionnez votre moyen de paiement sécurisé :
                </label>
                <span style="font-size:0.75rem; background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46; padding:3px 8px; border-radius:12px; font-weight:700;">
                    <i class="fa-solid fa-phone"></i> Compte OSHDI : <?php echo defined('PAYMENT_PHONE') ? PAYMENT_PHONE : '+225 07 47 16 64 61'; ?>
                </span>
            </div>
            <div class="payment-methods-grid">
                <!-- Wave -->
                <div class="pay-method-card active" data-method="wave">
                    <div class="pay-method-icon" style="color:#00B2FE;"><i class="fa-solid fa-water"></i></div>
                    <div class="pay-method-info">
                        <strong>Wave Money</strong>
                        <span>CI, Sénégal, Mali...</span>
                    </div>
                </div>

                <!-- Orange Money -->
                <div class="pay-method-card" data-method="orange">
                    <div class="pay-method-icon" style="color:#FF7900;"><i class="fa-solid fa-mobile-screen"></i></div>
                    <div class="pay-method-info">
                        <strong>Orange Money</strong>
                        <span>Afrique & International</span>
                    </div>
                </div>

                <!-- MTN Mobile Money -->
                <div class="pay-method-card" data-method="mtn">
                    <div class="pay-method-icon" style="color:#FFCC00;"><i class="fa-solid fa-phone-volume"></i></div>
                    <div class="pay-method-info">
                        <strong>MTN MoMo</strong>
                        <span>Mobile Money</span>
                    </div>
                </div>

                <!-- Moov Money -->
                <div class="pay-method-card" data-method="moov">
                    <div class="pay-method-icon" style="color:#005BAC;"><i class="fa-solid fa-tower-cell"></i></div>
                    <div class="pay-method-info">
                        <strong>Moov Money</strong>
                        <span>Réseau Moov</span>
                    </div>
                </div>

                <!-- Carte Bancaire -->
                <div class="pay-method-card" data-method="card">
                    <div class="pay-method-icon" style="color:#2563EB;"><i class="fa-solid fa-credit-card"></i></div>
                    <div class="pay-method-info">
                        <strong>Carte Bancaire</strong>
                        <span>Visa / Mastercard</span>
                    </div>
                </div>

                <!-- PayPal -->
                <div class="pay-method-card" data-method="paypal">
                    <div class="pay-method-icon" style="color:#003087;"><i class="fa-brands fa-paypal"></i></div>
                    <div class="pay-method-info">
                        <strong>PayPal</strong>
                        <span>Paiement Mondial</span>
                    </div>
                </div>

                <!-- Crypto -->
                <div class="pay-method-card" data-method="crypto" style="grid-column: span 2;">
                    <div class="pay-method-icon" style="color:#F59E0B;"><i class="fa-brands fa-bitcoin"></i></div>
                    <div class="pay-method-info">
                        <strong>Cryptomonnaies (USDT, BTC, ETH)</strong>
                        <span>Règlement décentralisé international</span>
                    </div>
                </div>
            </div>

            <!-- Détails contextuels selon le moyen sélectionné -->
            <div id="paymentMethodDetails">
                <div style="margin-top:16px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Numéro Wave (Côte d'Ivoire, Sénégal...)</label>
                    <div class="input-group">
                        <i class="fa-solid fa-mobile-screen"></i>
                        <input type="tel" name="payer_phone" required class="form-control" placeholder="+225 07 00 00 00 00">
                    </div>
                    <small style="color:#64748B; font-size:0.75rem; display:block; margin-top:4px;">
                        <i class="fa-solid fa-shield-halved"></i> Une demande d'autorisation sécurisée ou USSD sera envoyée sur ce numéro.
                    </small>
                </div>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" class="btn btn-gold btn-lg" style="width:100%;">
                    <i class="fa-solid fa-shield-halved"></i> Confirmer & Régler Maintenant
                </button>
            </div>
            
            <p style="text-align:center; font-size:0.75rem; color:#94A3B8; margin-top:14px;">
                <i class="fa-solid fa-lock"></i> Transaction chiffrée SSL 256 bits conforme aux normes bancaires et de protection des données.
            </p>
        </form>
    </div>
</div>
