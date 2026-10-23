<?php
$page_title = "Accueil | Restauration des Vies, Charité & Prière";
$page_description = "OSHDI (Œuvres Spirituelles et Humanitaires Divines) est une institution dédiée à la restauration des vies brisées : malades, orphelins, veuves, prisonniers, prières de délivrance et concours Africa Best Menu.";
$page_keywords = "OSHDI, œuvres spirituelles, humanitaire divine, prière de délivrance, souveraineté de dieu, aide orphelins veuves, REMDIS santé, concours culinaire ABM, Côte d'Ivoire, Afrique";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Récupération des formations récentes
$stmtFormations = $pdo->query("SELECT * FROM formations LIMIT 3");
$recentFormations = $stmtFormations->fetchAll();

// Récupération des filiales
$stmtBranches = $pdo->query("SELECT * FROM branches_activites LIMIT 3");
$recentBranches = $stmtBranches->fetchAll();

// Récupération des partenaires actifs
$stmtPartners = $pdo->query("SELECT * FROM espaces_partenaires WHERE statut = 'actif' LIMIT 3");
$recentPartners = $stmtPartners->fetchAll();
?>

<!-- ================= HERO SECTION ================= -->
<section class="hero-section">
    <div class="hero-container">
        <!-- Contenu Gauche -->
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fa-solid fa-dove"></i> <?= t('programs_subtitle') ?>
            </div>
            <h1>
                <?= t('hero_title') ?>
            </h1>
            <p>
                <?= t('hero_subtitle') ?>
            </p>
            <div class="hero-actions">
                <a href="dons.php" class="btn btn-gold btn-lg">
                    <i class="fa-solid fa-heart"></i> <?= t('hero_btn_donate') ?>
                </a>
                <a href="formation.php" class="btn btn-outline-white btn-lg">
                    <i class="fa-solid fa-graduation-cap"></i> <?= t('hero_btn_training') ?>
                </a>
                <a href="souscription.php" class="btn btn-accent btn-lg">
                    <i class="fa-solid fa-file-pen"></i> <?= t('hero_btn_subscribe') ?>
                </a>
            </div>

            <div style="display:flex; align-items:center; gap:16px; color:#94A3B8; font-size:0.85rem;">
                <div style="display:flex; gap:-8px;">
                    <i class="fa-solid fa-shield-halved" style="color:#10B981; font-size:1.2rem;"></i>
                </div>
                <span>Paiements certifiés Wave, Orange Money, Moov, MTN, Carte Bancaire & Cryptomonnaies</span>
            </div>
        </div>

        <!-- Carte de Soutien & Don Immédiat (Droite) -->
        <div class="hero-card">
            <div class="hero-card-header">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <div>
                    <h3>Faire une Offrande / Don</h3>
                    <p>Votre générosité restaure une vie aujourd'hui</p>
                </div>
            </div>

            <!-- Onglets Type de Don -->
            <div class="quick-don-tabs">
                <button type="button" class="quick-tab active">Souveraineté</button>
                <button type="button" class="quick-tab">Dîme</button>
                <button type="button" class="quick-tab">Charité / Malades</button>
            </div>

            <!-- Boutons de montants prédéfinis -->
            <div class="quick-amount-presets">
                <button type="button" class="amount-btn" data-val="5000">5 000 F</button>
                <button type="button" class="amount-btn active" data-val="10000">10 000 F</button>
                <button type="button" class="amount-btn" data-val="25000">25 000 F</button>
                <button type="button" class="amount-btn" data-val="50000">50 000 F</button>
            </div>

            <!-- Montant personnalisé -->
            <input type="number" class="custom-amount-input" placeholder="Ou saisir un autre montant (FCFA)..." min="500">

            <!-- Bouton d'action -->
            <button type="button" id="btnHeroDonate" class="btn btn-gold" style="width:100%; padding:14px; font-size:1.05rem;">
                <i class="fa-solid fa-lock"></i> Valider mon Don en Ligne
            </button>

            <div style="display:flex; justify-content:center; gap:14px; margin-top:14px; font-size:0.75rem; color:#94A3B8;">
                <span><i class="fa-solid fa-check" style="color:#10B981;"></i> Reçu fiscal instantané</span>
                <span><i class="fa-solid fa-check" style="color:#10B981;"></i> 100% Sécurisé</span>
            </div>
        </div>
    </div>
</section>

<!-- ================= STATS SECTION ================= -->
<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number">5 840<span class="plus">+</span></div>
            <div class="stat-label"><?= t('stat_lives') ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-number">2 150<span class="plus">+</span></div>
            <div class="stat-label"><?= t('stat_aid') ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-number">980<span class="plus">+</span></div>
            <div class="stat-label"><?= t('stat_students') ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-number">360<span class="plus">+</span></div>
            <div class="stat-label"><?= t('stat_weddings') ?></div>
        </div>
    </div>
</section>

<!-- ================= SECTION 7 PROGRAMMES ================= -->
<section class="section" id="programmes">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Piliers d'Action</span>
            <h2 class="section-title"><?= t('programs_title') ?></h2>
            <p class="section-subtitle"><?= t('programs_subtitle') ?></p>
        </div>

        <div class="programs-grid">
            <!-- 1. Africa Best Menu (Or Divin) -->
            <div class="program-card theme-gold">
                <div class="program-badge">Entrepreneuriat</div>
                <div class="program-icon-box">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h3 class="program-title"><?= t('prog_abm_title') ?></h3>
                <p class="program-desc"><?= t('prog_abm_desc') ?></p>
                <div class="program-card-footer">
                    <span style="font-size:0.82rem; color:var(--oshdi-gold-dark); font-weight:700;"><i class="fa-solid fa-trophy"></i> Prix & Financement Restaurant</span>
                    <a href="programmes.php#abm" class="btn btn-outline btn-sm">Détails <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- 2. Célébration de la Souveraineté de Dieu (Bleu Céleste) -->
            <div class="program-card theme-blue">
                <div class="program-badge">Spiritualité</div>
                <div class="program-icon-box">
                    <i class="fa-solid fa-hands-praying"></i>
                </div>
                <h3 class="program-title"><?= t('prog_csd_title') ?></h3>
                <p class="program-desc"><?= t('prog_csd_desc') ?></p>
                <div class="program-card-footer">
                    <span style="font-size:0.82rem; color:var(--oshdi-blue); font-weight:700;"><i class="fa-solid fa-fire"></i> Prières & Délivrances</span>
                    <a href="programmes.php#csd" class="btn btn-outline btn-sm">Détails <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- 3. JORESC (Vert Sacré) -->
            <div class="program-card theme-green">
                <div class="program-badge">Portes Ouvertes</div>
                <div class="program-icon-box">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
                <h3 class="program-title"><?= t('prog_joresc_title') ?></h3>
                <p class="program-desc"><?= t('prog_joresc_desc') ?></p>
                <div class="program-card-footer">
                    <span style="font-size:0.82rem; color:var(--oshdi-green-dark); font-weight:700;"><i class="fa-solid fa-square-poll-vertical"></i> Sondage & Prise en charge</span>
                    <a href="souscription.php" class="btn btn-outline btn-sm">Souscrire <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- 4. Leader Universel (Or Divin) -->
            <div class="program-card theme-gold">
                <div class="program-badge">Éducation d'Élite</div>
                <div class="program-icon-box">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <h3 class="program-title"><?= t('prog_leader_title') ?></h3>
                <p class="program-desc"><?= t('prog_leader_desc') ?></p>
                <div class="program-card-footer">
                    <span style="font-size:0.82rem; color:var(--oshdi-gold-dark); font-weight:700;"><i class="fa-solid fa-award"></i> Bourses & Kits Scolaires</span>
                    <a href="programmes.php#leader" class="btn btn-outline btn-sm">Détails <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- 5. Objectif de Développement Humanitaire (Rouge Rubis) -->
            <div class="program-card theme-ruby">
                <div class="program-badge">Solidarité Familiale</div>
                <div class="program-icon-box">
                    <i class="fa-solid fa-rings-wedding"></i>
                </div>
                <h3 class="program-title"><?= t('prog_odh_title') ?></h3>
                <p class="program-desc"><?= t('prog_odh_desc') ?></p>
                <div class="program-card-footer">
                    <span style="font-size:0.82rem; color:var(--oshdi-red); font-weight:700;"><i class="fa-solid fa-heart-pulse"></i> Mariages Collectifs</span>
                    <a href="programmes.php#odh" class="btn btn-outline btn-sm">Détails <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- 6. OSHDI CHARITÉ (Rouge Rubis & Or) -->
            <div class="program-card theme-ruby">
                <div class="program-badge">Secours & Dons</div>
                <div class="program-icon-box">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3 class="program-title"><?= t('prog_charite_title') ?></h3>
                <p class="program-desc"><?= t('prog_charite_desc') ?></p>
                <div class="program-card-footer">
                    <span style="font-size:0.82rem; color:var(--oshdi-red); font-weight:700;"><i class="fa-solid fa-handshake-angle"></i> Vivres & Visites aux prisons</span>
                    <a href="dons.php" class="btn btn-gold btn-sm">Les Dons <i class="fa-solid fa-heart"></i></a>
                </div>
            </div>

            <!-- 7. REMDIS (Santé Solidaire - Vert Sacré) -->
            <div class="program-card theme-green" style="grid-column: 1 / -1;">
                <div style="display:flex; flex-wrap:wrap; gap:28px; align-items:center;">
                    <div class="program-icon-box" style="width:80px; height:80px; font-size:2rem; margin-bottom:0;">
                        <i class="fa-solid fa-hospital-user"></i>
                    </div>
                    <div style="flex:1;">
                        <div class="program-badge" style="position:static; display:inline-block; margin-bottom:8px;">Réseau Médical</div>
                        <h3 class="program-title" style="font-size:1.6rem;"><?= t('prog_remdis_title') ?></h3>
                        <p class="program-desc" style="margin-bottom:0; font-size:1.05rem;"><?= t('prog_remdis_desc') ?></p>
                    </div>
                    <div>
                        <a href="souscription.php?prog=remdis" class="btn btn-emerald btn-lg">
                            <i class="fa-solid fa-file-medical"></i> Demander une Prise en Charge REMDIS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= BANNER SALLE VIRTUELLE (POINT 1) ================= -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #0F233E 50%, #0A192F 100%); color:#FFFFFF; padding:60px 24px; border-top: 3px solid; border-image: var(--oshdi-ribbon) 1; border-bottom: 3px solid;">
    <div class="container" style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:40px; align-items:center;">
        <div>
            <span class="section-tag tag-gold"><i class="fa-solid fa-satellite-dish"></i> Direct & Interactif</span>
            <h2 style="color:#FFFFFF; font-size:2.4rem; margin:14px 0;">Plateforme de Formation & Classe Virtuelle en Direct</h2>
            <p style="color:#CBD5E1; font-size:1.05rem; margin-bottom:24px;">
                Rejoignez nos sessions de formation interactives animées par nos maîtres et docteurs spirituels. 
                Toutes les technologies sont intégrées pour une expérience fluide : 
                <strong>appel vidéo haute définition, chat en temps réel, messages vocaux, partage d'écran et tableau blanc interactif</strong>.
            </p>
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <a href="classroom.php" class="btn btn-gold btn-lg">
                    <i class="fa-solid fa-video"></i> Entrer dans la Classe Virtuelle
                </a>
                <a href="formation.php" class="btn btn-outline-white btn-lg">
                    <i class="fa-solid fa-book-open"></i> Catalogue des Formations
                </a>
            </div>
        </div>
        <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); border-radius:18px; padding:28px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <div class="live-dot"></div>
                <strong style="color:#EF4444; font-size:0.9rem; text-transform:uppercase;">Session en Direct Aujourd'hui</strong>
            </div>
            <h4 style="color:#FFFFFF; font-size:1.25rem; margin-bottom:10px;">Séminaire OSHDI : Restauration des Vies Brisées</h4>
            <p style="font-size:0.88rem; color:#94A3B8; margin-bottom:18px;">
                Intervenant : Collège Pastoral OSHDI • 120 participants connectés en simultané depuis 6 pays.
            </p>
            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; font-size:0.8rem; color:#E2E8F0;">
                <div style="background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:6px;">
                    <i class="fa-solid fa-microphone" style="color:#10B981;"></i> Vocal & Audio HD
                </div>
                <div style="background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:6px;">
                    <i class="fa-solid fa-comments" style="color:#2563EB;"></i> Chat Instantané
                </div>
                <div style="background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:6px;">
                    <i class="fa-solid fa-desktop" style="color:#F59E0B;"></i> Partage d'Écran
                </div>
                <div style="background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:6px;">
                    <i class="fa-solid fa-lock" style="color:#EF4444;"></i> Support Protégé
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= FORMATIONS EN LIGNE (POINT 2) ================= -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Apprentissage Sécurisé</span>
            <h2 class="section-title">Formations en Ligne & Supports Protégés</h2>
            <p class="section-subtitle">
                Accédez à des cours vidéo et documents PDF certifiés en <strong>lecture seule non téléchargeable</strong> avec filigrane sécurisé.
            </p>
        </div>

        <div class="courses-grid">
            <?php foreach ($recentFormations as $f): ?>
                <div class="course-card">
                    <div class="course-media">
                        <img src="<?= htmlspecialchars($f['image_url']) ?>" alt="<?= htmlspecialchars($f['titre']) ?>">
                        <div class="course-badge-secure">
                            <i class="fa-solid fa-shield-halved"></i> Lecture Seule Anti-Copie
                        </div>
                        <div class="course-badge-category"><?= htmlspecialchars($f['categorie']) ?></div>
                    </div>
                    <div class="course-body">
                        <h4 class="course-title"><?= htmlspecialchars($f['titre']) ?></h4>
                        <p class="course-desc"><?= htmlspecialchars(substr($f['description'], 0, 115)) ?>...</p>
                        
                        <div class="course-meta">
                            <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($f['duree']) ?></span>
                            <span><i class="fa-solid fa-chalkboard-user"></i> <?= htmlspecialchars($f['formateur']) ?></span>
                        </div>

                        <div class="course-footer">
                            <div class="course-price"><?= formatMoney($f['prix']) ?></div>
                            <button type="button" class="btn btn-gold btn-sm" onclick="openPaymentModal({
                                title: 'Paiement Formation OSHDI',
                                itemName: '<?= addslashes($f['titre']) ?>',
                                amount: <?= $f['prix'] ?>,
                                amountFormatted: '<?= formatMoney($f['prix']) ?>',
                                type: 'formation',
                                itemId: <?= $f['id'] ?>
                            })">
                                <i class="fa-solid fa-lock-open"></i> S'inscrire & Payer
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:36px;">
            <a href="formation.php" class="btn btn-outline btn-lg">
                Voir toutes les formations et cours <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ================= SOUSCRIPTION SONDAGE D'OPINION (POINT 3) ================= -->
<section class="section" style="background:#F1F5F9;">
    <div class="container">
        <div style="background:#FFFFFF; border-radius:24px; padding:48px; box-shadow:var(--shadow-lg); border:1px solid var(--border-color); display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center;">
            <div>
                <span class="section-tag" style="background:rgba(37,99,235,0.1); color:#2563EB;">Point 3 • Adhésion & Prise en Charge</span>
                <h2 style="font-size:2.2rem; margin:14px 0; color:#0A192F;">Inscription en Ligne des Souscripteurs & Sondage</h2>
                <p style="color:#64748B; font-size:1rem; margin-bottom:20px; line-height:1.7;">
                    Réglez vos frais de souscription via <strong>Wave, Orange Money, Moov, MTN, Carte Bancaire ou Cryptomonnaies</strong> pour débloquer immédiatement votre 
                    <strong>BORDEREAU OFFICIEL DE SOUSCRIPTION DE SONDAGE D'OPINION</strong> et les documents d'assistance de l'institution OSHDI.
                </p>
                <ul style="list-style:none; margin-bottom:28px;">
                    <li style="margin-bottom:10px; display:flex; align-items:center; gap:10px; font-weight:600; font-size:0.92rem; color:#0F172A;">
                        <i class="fa-solid fa-circle-check" style="color:#059669;"></i> Accès direct au formulaire officiel de sondage d'opinion
                    </li>
                    <li style="margin-bottom:10px; display:flex; align-items:center; gap:10px; font-weight:600; font-size:0.92rem; color:#0F172A;">
                        <i class="fa-solid fa-circle-check" style="color:#059669;"></i> Transmission automatique de votre dossier par mail au secrétariat OSHDI
                    </li>
                    <li style="margin-bottom:10px; display:flex; align-items:center; gap:10px; font-weight:600; font-size:0.92rem; color:#0F172A;">
                        <i class="fa-solid fa-circle-check" style="color:#059669;"></i> Délivrance du bordereau avec signature et QR Code certifié
                    </li>
                </ul>

                <a href="souscription.php" class="btn btn-gold btn-lg">
                    <i class="fa-solid fa-file-signature"></i> Commencer ma Souscription (2 000 FCFA)
                </a>
            </div>

            <div style="background:#0F172A; border-radius:18px; padding:32px; color:#FFFFFF; position:relative; overflow:hidden;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:14px; margin-bottom:20px;">
                    <div>
                        <span style="font-size:0.75rem; color:#F59E0B; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Document Officiel</span>
                        <h4 style="color:#FFFFFF; font-size:1.15rem;">Bordereau de Souscription & Sondage</h4>
                    </div>
                    <i class="fa-solid fa-stamp" style="font-size:2rem; color:#D97706;"></i>
                </div>

                <div style="background:rgba(255,255,255,0.06); border-radius:10px; padding:16px; font-size:0.85rem; color:#CBD5E1; margin-bottom:16px;">
                    <p style="margin-bottom:8px;"><strong>1. Renseignement d'identité :</strong> Nom, contact, région, ville, profession</p>
                    <p style="margin-bottom:8px;"><strong>2. Évaluation des besoins :</strong> Soins médicaux REMDIS, concours ABM, scolarité Leader Universel, mariage ODH</p>
                    <p><strong>3. Signature numérique :</strong> Authentification de souscription</p>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; color:#94A3B8;">
                    <span>Frais d'adhésion : <strong>2 000 FCFA</strong></span>
                    <span style="color:#10B981;"><i class="fa-solid fa-bolt"></i> Traitement instantané</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= RECHERCHE ACTIVITÉS & FILIALES (POINT 4) ================= -->
<section class="section" id="filiales">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Couverture Territoriale</span>
            <h2 class="section-title"><?= t('search_title') ?></h2>
            <p class="section-subtitle"><?= t('search_subtitle') ?></p>
        </div>

        <div class="search-box-card">
            <form action="recherche_activites.php" method="GET" class="search-inputs-grid">
                <div class="input-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" class="form-control" placeholder="<?= t('search_placeholder') ?>">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-map"></i>
                    <select name="region" class="form-control">
                        <option value="">Toutes les Régions</option>
                        <option value="Lagunes">Lagunes (Abidjan)</option>
                        <option value="Gbêkê">Gbêkê (Bouaké)</option>
                        <option value="Bélier">Bélier (Yamoussoukro)</option>
                        <option value="San-Pédro">San-Pédro</option>
                        <option value="Poro">Poro (Korhogo)</option>
                        <option value="Europe">International (Europe)</option>
                    </select>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-building-flag"></i>
                    <select name="type" class="form-control">
                        <option value="">Tous les Types</option>
                        <option value="Siège">Siège Central</option>
                        <option value="Filiale">Filiale</option>
                        <option value="Succursale">Succursale</option>
                        <option value="Représentation">Représentation</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-gold">
                    <i class="fa-solid fa-filter"></i> Rechercher
                </button>
            </form>
        </div>

        <!-- Aperçu de quelques représentations -->
        <div class="branches-grid">
            <?php foreach ($recentBranches as $b): ?>
                <div class="branch-card">
                    <div class="branch-header">
                        <span class="branch-type"><?= htmlspecialchars($b['type_entite']) ?></span>
                        <span style="font-size:0.75rem; color:#64748B;"><i class="fa-solid fa-globe"></i> <?= htmlspecialchars($b['pays']) ?></span>
                    </div>
                    <h4 class="branch-name"><?= htmlspecialchars($b['nom_branche']) ?></h4>
                    <div class="branch-location">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?= htmlspecialchars($b['ville']) ?>, <?= htmlspecialchars($b['region']) ?> (<?= htmlspecialchars($b['localite']) ?>)</span>
                    </div>
                    <div class="branch-activities">
                        <strong>Activités en cours :</strong><br>
                        <?= htmlspecialchars($b['activites_en_cours']) ?>
                    </div>
                    <div class="branch-contacts">
                        <span><i class="fa-solid fa-user-tie"></i> Responsable : <?= htmlspecialchars($b['responsable']) ?></span>
                        <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($b['telephone']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:30px;">
            <a href="recherche_activites.php" class="btn btn-outline">
                Voir toutes les représentations et activités en région <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ================= ESPACES PARTENAIRES & PUBLICITÉ (POINTS 5 & 6) ================= -->
<section class="section" style="background:#F8FAFC; border-top:1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Visibilité & Partenariats</span>
            <h2 class="section-title">Espaces Publicitaires & Partenaires</h2>
            <p class="section-subtitle">
                Donnez de la visibilité à votre entreprise, boutique en ligne ou physique, clinique ou agence de communication tout en soutenant l'action humanitaire.
            </p>
        </div>

        <div class="branches-grid" style="margin-bottom:36px;">
            <?php foreach ($recentPartners as $part): ?>
                <div class="branch-card" style="border-top: 3px solid #2563EB;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span class="branch-type" style="background:rgba(217,119,6,0.1); color:#D97706;"><?= htmlspecialchars($part['pack_type']) ?></span>
                        <span style="font-size:0.75rem; color:#059669; font-weight:700;"><i class="fa-solid fa-badge-check"></i> Partenaire Certifié</span>
                    </div>
                    <h4 class="branch-name"><?= htmlspecialchars($part['nom_entreprise']) ?></h4>
                    <p style="font-size:0.85rem; color:#64748B; margin-bottom:12px;"><?= htmlspecialchars($part['type_activite']) ?></p>
                    <p style="font-size:0.88rem; color:#334155; margin-bottom:14px;"><?= htmlspecialchars($part['description']) ?></p>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; color:#2563EB;">
                        <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($part['contact']) ?></span>
                        <a href="<?= htmlspecialchars($part['site_web']) ?>" target="_blank" style="text-decoration:underline;">Visiter <i class="fa-solid fa-up-right-from-square"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="background:linear-gradient(135deg, #0A192F, #1E293B); border-radius:20px; padding:36px; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
            <div>
                <h3 style="color:#FFFFFF; font-size:1.6rem; margin-bottom:6px;">Vous souhaitez promouvoir votre marque ou commerce ?</h3>
                <p style="color:#CBD5E1; font-size:0.95rem;">Réservez un espace publicitaire (bannières, articles sponsorisés, annuaire des boutiques).</p>
            </div>
            <a href="partenaires_espaces.php" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-store"></i> Découvrir les Packs & Réserver
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
