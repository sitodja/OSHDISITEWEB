<?php
$page_title = "Médiathèque Officielle : Vidéos, Photos & Cérémonies Humanitaires";
$page_description = "Explorez les reportages vidéos, photos officielles et témoignages vivants des cérémonies CSD, finales Africa Best Menu (ABM) et remises de dons humanitaires OSHDI.";
$page_keywords = "médiathèque OSHDI, vidéos prière CSD, finale Africa Best Menu, photos œuvres caritatives, témoignages délivrance, reportages ONG Côte d'Ivoire";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Évènements passés et récents
$events = [
    [
        'titre' => 'Grande Finale Africa Best Menu (ABM) - Édition 2026',
        'programme' => 'ABM',
        'type' => 'video',
        'date' => 'Août 2026',
        'lieu' => 'Palais de la Culture, Abidjan',
        'image' => 'assets/images/hero_banner.jpg',
        'desc' => 'Couronnement des 3 meilleurs chefs restaurateurs avec remise des chèques d\'installation et équipements professionnels.'
    ],
    [
        'titre' => 'Nuit de la Célébration de la Souveraineté de Dieu (CSD)',
        'programme' => 'CSD',
        'type' => 'video',
        'date' => 'Juillet 2026',
        'lieu' => 'Stade Municipal de Bouaké',
        'image' => 'assets/images/hero_banner.jpg',
        'desc' => 'Rassemblement de plus de 15 000 âmes dans la prière d\'intercession et de délivrance pour les vies brisées.'
    ],
    [
        'titre' => 'Journée Portes Ouvertes JORESC & Prise en Charge Médicale',
        'programme' => 'JORESC',
        'type' => 'image',
        'date' => 'Juin 2026',
        'lieu' => 'Yamoussoukro, Quartier Habitat',
        'image' => 'assets/images/hero_banner.jpg',
        'desc' => 'Dépouillement des fiches de sondage d\'opinion et délivrance des bons de soins REMDIS aux familles nécessiteuses.'
    ],
    [
        'titre' => 'Gala d\'Excellence Leader Universel : 300 Bourses Scolaires',
        'programme' => 'LEADER',
        'type' => 'image',
        'date' => 'Septembre 2026',
        'lieu' => 'Hôtel Ivoire, Abidjan',
        'image' => 'assets/images/hero_banner.jpg',
        'desc' => 'Cérémonie solennelle récompensant les meilleurs élèves orphelins et démunis de Côte d\'Ivoire.'
    ],
    [
        'titre' => 'Célébration des 50 Mariages Collectifs (ODH)',
        'programme' => 'ODH',
        'type' => 'video',
        'date' => 'Mai 2026',
        'lieu' => 'San-Pédro',
        'image' => 'assets/images/hero_banner.jpg',
        'desc' => 'Restauration de la dignité de 50 couples dans la grâce civile et divine avec banquet festif offert.'
    ],
    [
        'titre' => 'Mission Humanitaire OSHDI CHARITÉ : Maison d\'Arrêt & Hôpitaux',
        'programme' => 'CHARITE',
        'type' => 'image',
        'date' => 'Avril 2026',
        'lieu' => 'MACA & CHU de Treichville',
        'image' => 'assets/images/hero_banner.jpg',
        'desc' => 'Distribution de 10 tonnes de vivres, kits d\'hygiène et soutien moral et spirituel aux détenus et malades.'
    ]
];
?>

<!-- En-tête -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Mémoires & Témoignages Visuels</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;">Médiathèque des Évènements OSHDI</h1>
        <p style="color:#CBD5E1; max-width:800px; margin:0 auto; font-size:1.1rem;">
            Revivez en images et en vidéos les grandes actions socio-spirituelles et humanitaires menées à travers nos différentes représentations.
        </p>
    </div>
</section>

<div class="container" style="padding:50px 24px;">

    <!-- Filtres des Évènements -->
    <div style="display:flex; justify-content:center; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:40px;">
        <button type="button" class="btn btn-gold btn-sm event-filter-btn" data-filter="all">Tous les Évènements</button>
        <button type="button" class="btn btn-outline btn-sm event-filter-btn" data-filter="ABM">Africa Best Menu (ABM)</button>
        <button type="button" class="btn btn-outline btn-sm event-filter-btn" data-filter="CSD">Célébration CSD</button>
        <button type="button" class="btn btn-outline btn-sm event-filter-btn" data-filter="JORESC">JORESC Portes Ouvertes</button>
        <button type="button" class="btn btn-outline btn-sm event-filter-btn" data-filter="LEADER">Leader Universel</button>
        <button type="button" class="btn btn-outline btn-sm event-filter-btn" data-filter="ODH">Mariages ODH</button>
        <button type="button" class="btn btn-outline btn-sm event-filter-btn" data-filter="CHARITE">OSHDI Charité</button>
    </div>

    <!-- Grille des Médias -->
    <div class="courses-grid" id="eventsGrid">
        <?php foreach ($events as $ev): ?>
            <div class="course-card event-item" data-category="<?= htmlspecialchars($ev['programme']) ?>">
                <div class="course-media" style="cursor:pointer;" onclick="openMediaModal('<?= addslashes($ev['titre']) ?>', '<?= $ev['type'] ?>', '<?= htmlspecialchars($ev['image']) ?>')">
                    <img src="<?= htmlspecialchars($ev['image']) ?>" alt="<?= htmlspecialchars($ev['titre']) ?>">
                    
                    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:56px; height:56px; border-radius:50%; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; color:#FFFFFF; font-size:1.4rem; border:2px solid #F59E0B;">
                        <i class="fa-solid <?= $ev['type'] === 'video' ? 'fa-play' : 'fa-image' ?>"></i>
                    </div>

                    <div class="course-badge-secure" style="border-color:#F59E0B; color:#F59E0B;">
                        <?= $ev['type'] === 'video' ? '<i class="fa-solid fa-video"></i> Reportage Vidéo' : '<i class="fa-solid fa-camera"></i> Album Photos' ?>
                    </div>
                    <div class="course-badge-category"><?= htmlspecialchars($ev['programme']) ?></div>
                </div>

                <div class="course-body">
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:#D97706; font-weight:700; margin-bottom:8px;">
                        <span><i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($ev['date']) ?></span>
                        <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($ev['lieu']) ?></span>
                    </div>

                    <h4 class="course-title"><?= htmlspecialchars($ev['titre']) ?></h4>
                    <p class="course-desc"><?= htmlspecialchars($ev['desc']) ?></p>

                    <div class="course-footer" style="border-top:1px solid var(--border-color); padding-top:14px;">
                        <button type="button" class="btn btn-outline btn-sm" style="width:100%;" onclick="openMediaModal('<?= addslashes($ev['titre']) ?>', '<?= $ev['type'] ?>', '<?= htmlspecialchars($ev['image']) ?>')">
                            <i class="fa-solid fa-eye"></i> Visualiser le média
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Média Pop-up -->
<div id="mediaModal" class="modal-overlay">
    <div class="payment-modal" style="max-width:750px;">
        <div class="modal-header">
            <h3 id="mediaModalTitle" style="font-size:1.15rem; color:#FFFFFF;">Visualiseur d'Évènement OSHDI</h3>
            <button type="button" class="modal-close-btn" onclick="closeMediaModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px; text-align:center;">
            <div id="mediaModalContent" style="border-radius:12px; overflow:hidden; background:#0F172A; margin-bottom:16px;">
                <!-- Injecté dynamiquement -->
            </div>
            <p id="mediaModalDesc" style="font-size:0.9rem; color:#475569;"></p>
        </div>
    </div>
</div>

<script>
    function openMediaModal(title, type, imgUrl) {
        document.getElementById('mediaModalTitle').innerText = title;
        const container = document.getElementById('mediaModalContent');
        if (type === 'video') {
            container.innerHTML = `
                <div style="position:relative; padding-top:56.25%;">
                    <video controls autoplay style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;" poster="${imgUrl}">
                        <source src="assets/videos/demo_formation.mp4" type="video/mp4">
                    </video>
                </div>
            `;
        } else {
            container.innerHTML = `<img src="${imgUrl}" style="max-width:100%; max-height:480px; object-fit:contain; margin:0 auto; display:block;">`;
        }
        document.getElementById('mediaModal').classList.add('active');
    }

    function closeMediaModal() {
        const modal = document.getElementById('mediaModal');
        modal.classList.remove('active');
        document.getElementById('mediaModalContent').innerHTML = '';
    }

    // Filtres
    const filterBtns = document.querySelectorAll('.event-filter-btn');
    const eventCards = document.querySelectorAll('.event-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => {
                b.classList.remove('btn-gold');
                b.classList.add('btn-outline');
            });
            btn.classList.add('btn-gold');
            btn.classList.remove('btn-outline');

            const filter = btn.getAttribute('data-filter');
            eventCards.forEach(card => {
                const cat = card.getAttribute('data-category');
                if (filter === 'all' || cat === filter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
