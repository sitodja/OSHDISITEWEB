<!-- Footer OSHDI -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Col 1: Présentation -->
            <div class="footer-col">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <img src="assets/images/logo.png" alt="Logo OSHDI" style="width:48px; height:48px; border-radius:50%; border:2.5px solid var(--oshdi-green); box-shadow:0 0 12px rgba(34,197,94,0.35); background:#FFFFFF; object-fit:cover;">
                    <div>
                        <h4 style="margin-bottom:2px; padding-bottom:0; color:#FFFFFF; font-size:1.4rem; font-weight:800;">OSHDI</h4>
                        <span style="font-size:0.75rem; color:var(--oshdi-gold-bright); text-transform:uppercase; letter-spacing:1px; font-weight:700;">Œuvres Divines</span>
                    </div>
                </div>
                <p><?= t('footer_about') ?></p>
                <div style="display:flex; gap:12px; margin-top:14px;">
                    <a href="#" class="ctrl-btn" style="width:38px; height:38px; font-size:0.95rem; border-color:var(--oshdi-blue); color:var(--oshdi-blue);"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="ctrl-btn" style="width:38px; height:38px; font-size:0.95rem; border-color:var(--oshdi-red); color:var(--oshdi-red);"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" class="ctrl-btn" style="width:38px; height:38px; font-size:0.95rem; border-color:var(--oshdi-green); color:var(--oshdi-green);"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Col 2: Programmes Sociaux & Spirituels -->
            <div class="footer-col">
                <h4>Nos 7 Programmes</h4>
                <ul class="footer-links">
                    <li><a href="programmes.php#abm"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-gold);"></i> Africa Best Menu (ABM)</a></li>
                    <li><a href="programmes.php#csd"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-blue);"></i> Célébration Souveraineté (CSD)</a></li>
                    <li><a href="programmes.php#joresc"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-green);"></i> JORESC Portes Ouvertes</a></li>
                    <li><a href="programmes.php#leader"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-gold);"></i> Leader Universel</a></li>
                    <li><a href="programmes.php#odh"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-red);"></i> Objectif Dév. Humanitaire</a></li>
                    <li><a href="programmes.php#charite"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-red);"></i> OSHDI CHARITÉ</a></li>
                    <li><a href="programmes.php#remdis"><i class="fa-solid fa-angle-right" style="color:var(--oshdi-green);"></i> REMDIS Santé Solidaire</a></li>
                </ul>
            </div>

            <!-- Col 3: Liens & Services -->
            <div class="footer-col">
                <h4>Plateformes OSHDI</h4>
                <ul class="footer-links">
                    <li><a href="formation.php"><i class="fa-solid fa-angle-right"></i> Formations en Ligne</a></li>
                    <li><a href="classroom.php"><i class="fa-solid fa-angle-right"></i> Visioconférence</a></li>
                    <li><a href="souscription.php"><i class="fa-solid fa-angle-right"></i> Bordereau de Sondage</a></li>
                    <li><a href="recherche_activites.php"><i class="fa-solid fa-angle-right"></i> Recherche Filiales & Villes</a></li>
                    <li><a href="partenaires_espaces.php"><i class="fa-solid fa-angle-right"></i> Espaces Partenaires & Pub</a></li>
                    <li><a href="mediatheque.php"><i class="fa-solid fa-angle-right"></i> Galerie Photos & Vidéos</a></li>
                    <li><a href="dons.php"><i class="fa-solid fa-angle-right"></i> Espace Dons & Dîmes</a></li>
                    <li><a href="admin_paiements.php" style="color:var(--oshdi-gold-bright); font-weight:600;"><i class="fa-solid fa-server"></i> Console Passerelles Paiement</a></li>
                </ul>
            </div>

            <!-- Col 4: Siège & Contact -->
            <div class="footer-col">
                <h4>Contact & Secrétariat</h4>
                <p style="margin-bottom:8px;"><i class="fa-solid fa-location-dot" style="color:var(--oshdi-red); margin-right:8px;"></i> <?= SITE_ADDRESS ?></p>
                <p style="margin-bottom:8px;"><i class="fa-solid fa-phone" style="color:var(--oshdi-green); margin-right:8px;"></i> <?= SITE_PHONE ?></p>
                <p style="margin-bottom:16px;"><i class="fa-solid fa-envelope" style="color:var(--oshdi-gold); margin-right:8px;"></i> <?= SITE_EMAIL ?></p>
                
                <div style="background:rgba(255,255,255,0.05); padding:12px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); border-left:3px solid var(--oshdi-gold);">
                    <small style="color:var(--oshdi-gold-bright); font-weight:700; display:block; margin-bottom:4px;">Permanence de Prière & Écoute :</small>
                    <span style="font-size:0.8rem; color:#CBD5E1;">24h/24 et 7j/7 pour les personnes souffrantes et démunies.</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= SITE_FULL_NAME ?> (<?= SITE_NAME ?>). <?= t('footer_rights') ?></p>
        </div>
    </div>
</footer>

<!-- Inclusion du Modal de Paiement Universel -->
<?php require_once __DIR__ . '/payment_modal.php'; ?>

<!-- Scripts Principaux avec Cache-Busting Multi-Navigateurs -->
<script src="assets/js/main.js?v=<?= file_exists(__DIR__ . '/../assets/js/main.js') ? filemtime(__DIR__ . '/../assets/js/main.js') : time() ?>"></script>
<?php if (isset($extra_js)): ?>
    <script src="<?= htmlspecialchars($extra_js) ?>?v=<?= file_exists(__DIR__ . '/../' . $extra_js) ? filemtime(__DIR__ . '/../' . $extra_js) : time() ?>"></script>
<?php endif; ?>
</body>
</html>
