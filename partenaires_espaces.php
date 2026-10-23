<?php
$page_title = "Partenariats Économiques & Espaces Publicitaires Humanitaires";
$page_description = "Devenez partenaire stratégique de l'ONG OSHDI. Réservez vos espaces publicitaires et sponsors pour soutenir les programmes humanitaires et spirituels en Afrique.";
$page_keywords = "partenariat OSHDI, sponsor humanitaire, publicité caritative, mécénat entreprise, visibilité Afrique, impact social, RSE Côte d'Ivoire";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Récupération de tous les partenaires enregistrés
$stmt = $pdo->query("SELECT * FROM espaces_partenaires ORDER BY id DESC");
$partners = $stmt->fetchAll();
?>

<!-- En-tête -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Réseau Économique & Publicitaire</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;"><?= t('partner_title') ?></h1>
        <p style="color:#CBD5E1; max-width:800px; margin:0 auto; font-size:1.1rem;">
            <?= t('partner_subtitle') ?>
        </p>
    </div>
</section>

<div class="container" style="padding:60px 24px;">

    <!-- ================= SECTION DES OFFRES & PACKS PAYANTS ================= -->
    <div class="section-header">
        <span class="section-tag">Offres Professionnelles</span>
        <h2 class="section-title">Packs Publicitaires & Espaces Dédiés</h2>
        <p class="section-subtitle">
            Réglez votre souscription professionnelle en ligne via <strong>Wave, Orange Money, Moov, MTN, Carte Bancaire ou Cryptomonnaies</strong>.
        </p>
    </div>

    <div class="packs-grid">
        <!-- Pack 1 : Boutiques Physiques & En Ligne -->
        <div class="pack-card">
            <span style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700;">Commerces & Boutiques</span>
            <h3 style="font-size:1.5rem; color:#0A192F; margin-top:4px;">Pack Visibilité Boutique</h3>
            <div class="pack-price">50 000 <span style="font-size:1rem; font-weight:600; color:#64748B;">FCFA / trim.</span></div>
            <p style="font-size:0.88rem; color:#64748B;">Idéal pour les boutiques physiques, magasins d'habillement, e-commerce et artisans.</p>
            
            <ul class="pack-features">
                <li><i class="fa-solid fa-check"></i> Fiche complète dans l'annuaire des partenaires</li>
                <li><i class="fa-solid fa-check"></i> Insertion logo, photos et lien boutique en ligne</li>
                <li><i class="fa-solid fa-check"></i> Relais sur les réseaux sociaux OSHDI</li>
                <li><i class="fa-solid fa-check"></i> Accès à la communauté des fidèles et souscripteurs</li>
            </ul>

            <button type="button" class="btn btn-outline" style="width:100%;" onclick="openPaymentModal({
                title: 'Souscription Espace Publicitaire',
                itemName: 'Pack Visibilité Boutique (Trimestriel)',
                amount: 50000,
                amountFormatted: '50 000 FCFA',
                type: 'espace_partenaire'
            })">
                <i class="fa-solid fa-cart-shopping"></i> Réserver cet Espace
            </button>
        </div>

        <!-- Pack 2 : Entreprises & PME (Populaire) -->
        <div class="pack-card popular">
            <div class="pack-tag">Recommandé</div>
            <span style="font-size:0.75rem; text-transform:uppercase; color:#D97706; font-weight:700;">Entreprises & PME</span>
            <h3 style="font-size:1.5rem; color:#0A192F; margin-top:4px;">Pack Entreprise & Agence</h3>
            <div class="pack-price" style="color:#D97706;">100 000 <span style="font-size:1rem; font-weight:600; color:#64748B;">FCFA / sem.</span></div>
            <p style="font-size:0.88rem; color:#64748B;">Pour entreprises de communication, sociétés de services, cliniques et établissements privés.</p>
            
            <ul class="pack-features">
                <li><i class="fa-solid fa-check"></i> Bannière publicitaire en page d'accueil</li>
                <li><i class="fa-solid fa-check"></i> Encart dédié sur les pages de formation et classe virtuelle</li>
                <li><i class="fa-solid fa-check"></i> Mention spéciale partenaire dans les communiqués officiels</li>
                <li><i class="fa-solid fa-check"></i> Stand d'exposition prioritaire lors des journées JORESC</li>
            </ul>

            <button type="button" class="btn btn-gold" style="width:100%;" onclick="openPaymentModal({
                title: 'Souscription Espace Publicitaire',
                itemName: 'Pack Entreprise & Agence (Semestriel)',
                amount: 100000,
                amountFormatted: '100 000 FCFA',
                type: 'espace_partenaire'
            })">
                <i class="fa-solid fa-bullhorn"></i> Souscrire au Pack Entreprise
            </button>
        </div>

        <!-- Pack 3 : Sponsor Majeur & Évènements -->
        <div class="pack-card">
            <span style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700;">Grand Public</span>
            <h3 style="font-size:1.5rem; color:#0A192F; margin-top:4px;">Pack Sponsor Évènements</h3>
            <div class="pack-price">250 000 <span style="font-size:1rem; font-weight:600; color:#64748B;">FCFA / an</span></div>
            <p style="font-size:0.88rem; color:#64748B;">Sponsoring de nos grands évènements : Africa Best Menu, CSD et galas Leader Universel.</p>
            
            <ul class="pack-features">
                <li><i class="fa-solid fa-check"></i> Logo sur les supports officiels, affiches et vidéos retransmises</li>
                <li><i class="fa-solid fa-check"></i> Prise de parole institutionnelle lors des cérémonies de remise</li>
                <li><i class="fa-solid fa-check"></i> Espace publicitaire premium permanent sur tout le site web</li>
                <li><i class="fa-solid fa-check"></i> Certificat officiel de Bienfaiteur Humanitaire OSHDI</li>
            </ul>

            <button type="button" class="btn btn-outline" style="width:100%;" onclick="openPaymentModal({
                title: 'Souscription Sponsoring Évènement',
                itemName: 'Pack Sponsor Évènements Majeurs OSHDI',
                amount: 250000,
                amountFormatted: '250 000 FCFA',
                type: 'espace_partenaire'
            })">
                <i class="fa-solid fa-award"></i> Devenir Sponsor Officiel
            </button>
        </div>
    </div>

    <!-- ================= VITRINE DES PARTENAIRES ACTUELS ================= -->
    <div class="section-header" style="margin-top:40px;">
        <span class="section-tag">Annonces & Boutiques Partenaires</span>
        <h2 class="section-title">Nos Partenaires & Commerces Référencés</h2>
        <p class="section-subtitle">Découvrez les entreprises et boutiques qui soutiennent les œuvres divines d'OSHDI.</p>
    </div>

    <div class="branches-grid">
        <?php foreach ($partners as $p): ?>
            <div class="branch-card" style="border-left: 4px solid #F59E0B;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <span class="branch-type"><?= htmlspecialchars($p['pack_type']) ?></span>
                    <span style="font-size:0.75rem; color:#10B981; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Partenaire Actif</span>
                </div>

                <h3 class="branch-name" style="font-size:1.3rem;"><?= htmlspecialchars($p['nom_entreprise']) ?></h3>
                <p style="color:#D97706; font-size:0.85rem; font-weight:600; margin-bottom:8px;">
                    <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($p['type_activite']) ?>
                </p>
                <p style="font-size:0.9rem; color:#475569; margin-bottom:16px; line-height:1.6;">
                    <?= htmlspecialchars($p['description']) ?>
                </p>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:12px; font-size:0.85rem;">
                    <span><i class="fa-solid fa-phone" style="color:#10B981;"></i> <?= htmlspecialchars($p['contact']) ?></span>
                    <?php if (!empty($p['site_web'])): ?>
                        <a href="<?= htmlspecialchars($p['site_web']) ?>" target="_blank" class="btn btn-outline btn-sm">
                            Visiter la Boutique <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ================= ÉVÈNEMENTS EN PARTENARIAT ================= -->
    <div style="margin-top:60px; background:#0F172A; border-radius:24px; padding:40px; color:#FFFFFF;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; margin-bottom:30px;">
            <div>
                <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Calendrier Partenariats</span>
                <h3 style="color:#FFFFFF; font-size:1.8rem; margin-top:6px;">Prochains Évènements Sponsorisés</h3>
            </div>
            <a href="dons.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-handshake"></i> Proposer un Évènement</a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap:20px;">
            <div style="background:rgba(255,255,255,0.06); border-radius:14px; padding:20px; border:1px solid rgba(255,255,255,0.1);">
                <div style="font-size:0.75rem; color:#F59E0B; font-weight:700; margin-bottom:4px;">15 NOVEMBRE 2026 • ABIDJAN</div>
                <h4 style="color:#FFFFFF; font-size:1.15rem; margin-bottom:8px;">Salon Gastronomique Africa Best Menu & Foires aux Épices</h4>
                <p style="font-size:0.85rem; color:#CBD5E1;">Exposition des lauréats, stands de dégustation et opportunités d'investissement traiteur.</p>
            </div>

            <div style="background:rgba(255,255,255,0.06); border-radius:14px; padding:20px; border:1px solid rgba(255,255,255,0.1);">
                <div style="font-size:0.75rem; color:#10B981; font-weight:700; margin-bottom:4px;">10 DÉCEMBRE 2026 • BOUAKÉ</div>
                <h4 style="color:#FFFFFF; font-size:1.15rem; margin-bottom:8px;">Journée Médicale REMDIS & Dons aux Orphelinats</h4>
                <p style="font-size:0.85rem; color:#CBD5E1;">Consultations foraines gratuites et distribution de médicaments avec nos cliniques partenaires.</p>
            </div>

            <div style="background:rgba(255,255,255,0.06); border-radius:14px; padding:20px; border:1px solid rgba(255,255,255,0.1);">
                <div style="font-size:0.75rem; color:#3B82F6; font-weight:700; margin-bottom:4px;">31 DÉCEMBRE 2026 • NUIT PROPHÉTIQUE</div>
                <h4 style="color:#FFFFFF; font-size:1.15rem; margin-bottom:8px;">Grande Célébration de la Souveraineté de Dieu (CSD)</h4>
                <p style="font-size:0.85rem; color:#CBD5E1;">Rassemblement national retransmis en direct avec diffusion des bannières de tous nos partenaires.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
