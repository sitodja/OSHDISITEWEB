<?php
$page_title = "Les 7 Grands Programmes Sociaux & Spirituels";
$page_description = "Découvrez les 7 piliers d'action OSHDI : Africa Best Menu (ABM), Célébration Souveraineté de Dieu (CSD), JORESC, Leader Universel, ODH, OSHDI CHARITÉ et REMDIS.";
$page_keywords = "programmes OSHDI, Africa Best Menu, ABM, CSD prière délivrance, JORESC portes ouvertes, Leader Universel bourse, ODH mariage collectif, OSHDI CHARITÉ, REMDIS santé solidaire";

// Schema.org FAQPage pour affichage enrichi dans les résultats Google
$extra_schema_json = '{
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Qu\'est-ce que le programme Africa Best Menu (ABM) de l\'OSHDI ?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Le Concours Culinaire International Africa Best Menu (ABM) valorise l\'art gastronomique africain et finance l\'installation professionnelle et l\'ouverture de restaurants pour les lauréats."
      }
    },
    {
      "@type": "Question",
      "name": "Comment fonctionne le Réseau REMDIS pour la santé des personnes démunies ?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "REMDIS est un réseau médical solidaire qui négocie des réductions tarifaires auprès de cliniques et pharmacies partenaires et prend en charge les soins des personnes vulnérables."
      }
    },
    {
      "@type": "Question",
      "name": "Comment souscrire ou bénéficier de l\'aide de l\'OSHDI ?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Vous pouvez remplir le bordereau officiel de sondage et de souscription directement en ligne sur la page souscription.php du site officiel OSHDI."
      }
    }
  ]
}';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- En-tête de page -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #020C1B 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Missions & Réalisations</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;">Les 7 Grands Piliers d'Action OSHDI</h1>
        <p style="color:#CBD5E1; max-width:750px; margin:0 auto; font-size:1.1rem;">
            Des programmes complets alliant foi vivante, soutien matériel, éducation d'élite, santé solidaire et développement économique pour restaurer chaque être humain.
        </p>
    </div>
</section>

<!-- Contenu des 7 Programmes -->
<section class="section">
    <div class="container" style="display:flex; flex-direction:column; gap:60px;">

        <!-- 1. Africa Best Menu (ABM) -->
        <div id="abm" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(217,119,6,0.1); color:#D97706;">Programme Économique & Métiers</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-calendar"></i> Édition Annuelle & Régionale</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-utensils" style="color:#D97706; margin-right:8px;"></i> Africa Best Menu (ABM)
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    <strong>Africa Best Menu (ABM)</strong> est un concours culinaire d'envergure continental et national créé par OSHDI. 
                    Il a pour objectif d'identifier les talents culinaires les plus méritants, souvent issus de milieux modestes, 
                    et de leur accorder des prix d'excellence pour <strong>les financer et les accompagner dans l'ouverture et la gestion de leur propre restaurant</strong>.
                </p>
                <div style="background:#FFFBEB; border-left:4px solid #F59E0B; padding:14px; border-radius:6px; margin-bottom:24px; font-size:0.9rem; color:#92400E;">
                    <strong>Impact OSHDI :</strong> Déjà plus de 45 restaurants et services traiteurs financés et inaugurés pour rendre les bénéficiaires financièrement autonomes et employeurs de jeunes démunis.
                </div>
                <div style="display:flex; gap:14px; flex-wrap:wrap;">
                    <a href="souscription.php?prog=abm" class="btn btn-gold">
                        <i class="fa-solid fa-trophy"></i> Postuler au Concours ABM
                    </a>
                    <button type="button" class="btn btn-outline" onclick="openPaymentModal({
                        title: 'Parrainage & Soutien Concours ABM',
                        itemName: 'Soutien Lauréats Africa Best Menu (ABM)',
                        amount: 25000,
                        amountFormatted: '25 000 FCFA',
                        type: 'don'
                    })">
                        <i class="fa-solid fa-hand-holding-heart"></i> Parrainer un Cuisinier
                    </button>
                </div>
            </div>
            <div style="background:var(--bg-light); border-radius:14px; padding:24px; border:1px solid var(--border-color);">
                <h4 style="margin-bottom:14px;"><i class="fa-solid fa-list-check" style="color:#D97706;"></i> Étapes de sélection ABM</h4>
                <ol style="margin-left:20px; color:#475569; font-size:0.92rem; line-height:1.8;">
                    <li>Inscription en ligne des candidats via le sondage OSHDI</li>
                    <li>Épreuves régionales de cuisine traditionnelle et moderne</li>
                    <li>Évaluation par des chefs renommés et experts en hygiène</li>
                    <li>Grande Finale retransmise en direct sur la plateforme</li>
                    <li>Dotation financière, matériel de cuisine et suivi d'ouverture</li>
                </ol>
            </div>
        </div>

        <!-- 2. Célébration de la Souveraineté de Dieu (CSD) -->
        <div id="csd" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(37,99,235,0.1); color:#2563EB;">Pilier Spirituel & Délivrance</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-church"></i> Rassemblements & Vigiles</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-hands-praying" style="color:#2563EB; margin-right:8px;"></i> Célébration de la Souveraineté de Dieu (CSD)
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    La <strong>Célébration de la Souveraineté de Dieu (CSD)</strong> est le socle spirituel de l'institution OSHDI. 
                    Il s'agit de louange, d'adoration et de prières d'intercession pour 
                    proclamer le règne de Dieu sur les vies brisées, briser les liens de pauvreté et de malédiction, 
                    et apporter la guérison divine aux malades incurables.
                </p>
                <div style="background:#EFF6FF; border-left:4px solid #2563EB; padding:14px; border-radius:6px; margin-bottom:24px; font-size:0.9rem; color:#1E40AF;">
                    <strong>Fondement :</strong> « L'Éternel soutient tous ceux qui tombent, et Il redresse tous ceux qui sont courbés. » (Psaume 145:14).
                </div>
                <div style="display:flex; gap:14px; flex-wrap:wrap;">
                    <a href="classroom.php" class="btn btn-accent">
                        <i class="fa-solid fa-video"></i> Suivre la CSD en Direct
                    </a>
                    <a href="dons.php?type=offrande" class="btn btn-gold">
                        <i class="fa-solid fa-gift"></i> Offrande de Souveraineté
                    </a>
                </div>
            </div>
            <div style="background:#0F172A; border-radius:14px; padding:28px; color:#FFFFFF;">
                <h4 style="color:#F59E0B; margin-bottom:14px;"><i class="fa-solid fa-bullhorn"></i> Programme des Assemblées CSD</h4>
                <ul style="list-style:none; font-size:0.9rem; line-height:2; color:#CBD5E1;">
                    <li><i class="fa-solid fa-calendar-day" style="color:#F59E0B; margin-right:8px;"></i> Mercredis : Délivrance & Prière de Guérison</li>
                    <li><i class="fa-solid fa-calendar-day" style="color:#F59E0B; margin-right:8px;"></i> Vendredis : Nuit de la Souveraineté Divine</li>
                    <li><i class="fa-solid fa-calendar-day" style="color:#F59E0B; margin-right:8px;"></i> Dimanches : Prière d'Actions de Grâces et Bénédictions</li>
                </ul>
            </div>
        </div>

        <!-- 3. JORESC -->
        <div id="joresc" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(5,150,105,0.1); color:#059669;">Consultation Citoyenne & Sociale</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-door-open"></i> Journée Portes Ouvertes</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-clipboard-question" style="color:#059669; margin-right:8px;"></i> JORESC (Journée Portes Ouvertes)
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    <strong>JORESC</strong> est la journée porte ouverte de OSHDI permettant aux populations de venir s'exprimer 
                    et d'exprimer leurs besoins à travers un <strong>sondage d'opinion approfondi</strong>. 
                    À l'issue du sondage, chaque participant ou famille identifiée bénéficie d'une 
                    <strong>prise en charge spirituelle ou socio-médicale</strong>.
                </p>
                <div style="background:#ECFDF5; border-left:4px solid #059669; padding:14px; border-radius:6px; margin-bottom:24px; font-size:0.9rem; color:#065F46;">
                    <strong>Modalités :</strong> La souscription au bordereau de sondage d'opinion est accessible en ligne ou par nos agents sur le terrain ou dans nos différentes filiales régionales.
                </div>
                <div style="display:flex; gap:14px;">
                    <a href="souscription.php" class="btn btn-emerald">
                        <i class="fa-solid fa-file-signature"></i> Remplir le Bordereau de Sondage
                    </a>
                </div>
            </div>
            <div style="background:var(--bg-light); border-radius:14px; padding:24px; border:1px solid var(--border-color);">
                <h4 style="margin-bottom:12px;"><i class="fa-solid fa-check-double" style="color:#059669;"></i> Prises en charge accordées</h4>
                <ul style="list-style:none; font-size:0.9rem; line-height:1.8; color:#475569;">
                    <li><i class="fa-solid fa-check" style="color:#059669; margin-right:6px;"></i> Prise en charge de consultations spirituelles au préalable</li>
                    <li><i class="fa-solid fa-check" style="color:#059669; margin-right:6px;"></i> Dotation de fonds de commerce pour veuves</li>
                    <li><i class="fa-solid fa-check" style="color:#059669; margin-right:6px;"></i> Scolarisation complète d'orphelins</li>
                    <li><i class="fa-solid fa-check" style="color:#059669; margin-right:6px;"></i> Assistance juridique pour les personnes incarcérées</li>
                </ul>
            </div>
        </div>

        <!-- 4. Leader Universel -->
        <div id="leader" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(217,119,6,0.1); color:#D97706;">Excellence Académique & Mentorat</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-school"></i> Écoles & Universités</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-user-graduate" style="color:#D97706; margin-right:8px;"></i> Leader Universel
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    Le programme <strong>Leader Universel</strong> a pour vocation de détecter et de récompenser les meilleurs élèves et étudiants 
                    issus de milieux défavorisés. OSHDI leur octroie des <strong>bourses d'études, des ordinateurs portables, des fournitures complètes</strong> 
                    et un mentorat de leadership fondé sur les valeurs éthiques et spirituelles.
                </p>
                <div style="display:flex; gap:14px;">
                    <a href="souscription.php?prog=leader" class="btn btn-gold">
                        <i class="fa-solid fa-pen-nib"></i> Inscrire un Élève / Candidat
                    </a>
                    <button type="button" class="btn btn-outline" onclick="openPaymentModal({
                        title: 'Financement d\'une Bourse Leader Universel',
                        itemName: 'Bourse d\'étude Leader Universel OSHDI',
                        amount: 30000,
                        amountFormatted: '30 000 FCFA',
                        type: 'don'
                    })">
                        <i class="fa-solid fa-graduation-cap"></i> Financer une Bourse
                    </button>
                </div>
            </div>
            <div style="background:#FFFBEB; border-radius:14px; padding:24px; border:1px solid rgba(217,119,6,0.2);">
                <h4 style="color:#92400E; margin-bottom:12px;"><i class="fa-solid fa-medal"></i> Prix d'Excellence Remis</h4>
                <p style="font-size:0.88rem; color:#78350F; line-height:1.7;">
                    Chaque année, plus de 300 lauréats reçoivent leurs kits d'excellence lors d'une grande cérémonie de gala 
                    présidée par le directoire de l'institution OSHDI.
                </p>
            </div>
        </div>

        <!-- 5. Objectif de Développement Humanitaire (ODH) - Rouge Rubis Sacré -->
        <div id="odh" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(220,38,38,0.1); color:var(--oshdi-red);">Restauration du Foyer</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-rings-wedding"></i> Célébrations Collectives</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-heart" style="color:var(--oshdi-red); margin-right:8px;"></i> Objectif de Développement Humanitaire (ODH)
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    <strong>ODH</strong> organise des <strong>mariages collectifs d'honneur</strong> pour les couples vivant en concubinage 
                    depuis de longues années par manque de moyens financiers pour célébrer leur union civile et religieuse. 
                    OSHDI prend en charge les frais administratifs, les alliances, les tenues nuptiales, la réception et le banquet.
                </p>
                <div style="display:flex; gap:14px;">
                    <a href="souscription.php?prog=odh" class="btn btn-ruby">
                        <i class="fa-solid fa-heart"></i> Inscrire un Couple à l'ODH
                    </a>
                </div>
            </div>
            <div style="background:#FEF2F2; border-radius:14px; padding:24px; border:1px solid rgba(220,38,38,0.2);">
                <h4 style="color:var(--oshdi-red-dark); margin-bottom:12px;"><i class="fa-solid fa-gift"></i> Prise en charge des Mariages à un prix forfaitaire à préciser dans les documents de OSHDI</h4>
                <ul style="list-style:none; font-size:0.9rem; line-height:1.8; color:#991B1B;">
                    <li><i class="fa-solid fa-check" style="color:var(--oshdi-red); margin-right:6px;"></i> Dossiers civils d'état civil en mairie</li>
                    <li><i class="fa-solid fa-check" style="color:var(--oshdi-red); margin-right:6px;"></i> Bénédiction nuptiale divine</li>
                    <li><i class="fa-solid fa-check" style="color:var(--oshdi-red); margin-right:6px;"></i> Repas de fête offert aux familles</li>
                    <li><i class="fa-solid fa-check" style="color:var(--oshdi-red); margin-right:6px;"></i> Kit d'installation électroménager du foyer</li>
                </ul>
            </div>
        </div>

        <!-- 6. OSHDI CHARITÉ -->
        <div id="charite" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(217,119,6,0.1); color:#D97706;">Action Humanitaire Directe</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-hand-holding-heart"></i> Secours d'Urgence</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-box-open" style="color:#D97706; margin-right:8px;"></i> OSHDI CHARITÉ
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    <strong>OSHDI CHARITÉ</strong> est le bras d'amour permanent de l'institution : 
                    visites et distributions régulières de dons alimentaires, hygiéniques et vestimentaires 
                    aux <strong>malades dans les hôpitaux, aux orphelinats, aux veuves délaissées, aux détenus des maisons d'arrêt et de correction</strong> 
                    et à toutes les personnes sans abri.
                </p>
                <div style="display:flex; gap:14px;">
                    <a href="dons.php" class="btn btn-gold">
                        <i class="fa-solid fa-heart"></i> Les Dons pour OSHDI CHARITÉ
                    </a>
                </div>
            </div>
            <div style="background:#0F172A; border-radius:14px; padding:24px; color:#FFFFFF;">
                <h4 style="color:#F59E0B; margin-bottom:12px;"><i class="fa-solid fa-truck-ramp-box"></i> Campagnes de Distribution</h4>
                <p style="font-size:0.88rem; color:#CBD5E1; line-height:1.7;">
                    Chaque mois, des convois humanitaires OSHDI sillonnent les villes et villages pour apporter un soulagement concret 
                    et une prière de délivrance aux personnes dans l'épreuve.
                </p>
            </div>
        </div>

        <!-- 7. REMDIS (Réseau Médical Divine Santé) -->
        <div id="remdis" style="background:#FFFFFF; border-radius:20px; border:1px solid var(--border-color); padding:40px; box-shadow:var(--shadow-md); display:grid; grid-template-columns:1.2fr 0.8fr; gap:36px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="program-badge" style="position:static; background:rgba(16,185,129,0.1); color:#10B981;">Santé & Soins Hospitaliers</span>
                    <span style="font-size:0.85rem; color:#64748B;"><i class="fa-solid fa-stethoscope"></i> Réseau Conventionné</span>
                </div>
                <h2 style="font-size:2.2rem; margin-bottom:16px; color:#0A192F;">
                    <i class="fa-solid fa-hospital-user" style="color:#10B981; margin-right:8px;"></i> REMDIS (Réseau Médical Divine Santé)
                </h2>
                <p style="color:#475569; font-size:1.02rem; line-height:1.7; margin-bottom:18px;">
                    <strong>REMDIS</strong> est une alliance stratégique établie entre OSHDI, des hôpitaux généraux, 
                    des cliniques privées, des laboratoires et des pharmacies. Cette convention permet aux bénéficiaires et souscripteurs 
                    d'accéder à des <strong>soins de santé de pointe, des examens spécialisés et des médicaments à coûts solidaires fortement réduits</strong>.
                </p>
                <div style="display:flex; gap:14px;">
                    <a href="souscription.php?prog=remdis" class="btn btn-emerald">
                        <i class="fa-solid fa-file-medical"></i> Demander une Prise en Charge Médicale
                    </a>
                </div>
            </div>
            <div style="background:#F0FDF4; border-radius:14px; padding:24px; border:1px solid rgba(16,185,129,0.2);">
                <h4 style="color:#166534; margin-bottom:12px;"><i class="fa-solid fa-notes-medical"></i> Avantages Réseau REMDIS</h4>
                <ul style="list-style:none; font-size:0.9rem; line-height:1.8; color:#14532D;">
                    <li><i class="fa-solid fa-check" style="color:#10B981; margin-right:6px;"></i> Réductions de 40% à 70% sur les consultations</li>
                    <li><i class="fa-solid fa-check" style="color:#10B981; margin-right:6px;"></i> Prise en charge chirurgicale d'urgence</li>
                    <li><i class="fa-solid fa-check" style="color:#10B981; margin-right:6px;"></i> Bons d'ordonnance pharmaceutiques solidaires</li>
                    <li><i class="fa-solid fa-check" style="color:#10B981; margin-right:6px;"></i> Suivi post-opératoire et soutien spirituel</li>
                </ul>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
