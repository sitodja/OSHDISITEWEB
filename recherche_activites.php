<?php
$page_title = "Délégations Régionales & Activités sur le Terrain";
$page_description = "Trouvez les délégations régionales, filiales et activités caritatives de l'ONG OSHDI en Côte d'Ivoire et à l'international (Abidjan, Bouaké, Yamoussoukro, Daloa, Korhogo).";
$page_keywords = "filiales OSHDI, délégations régionales, activités humanitaires, églises partenaires, Yamoussoukro, Abidjan, Bouaké, secours spirituel, contact région";
$extra_js = "assets/js/search_filter.js";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Requête de base
$query = trim($_GET['q'] ?? '');
$regionFilter = trim($_GET['region'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');

$sql = "SELECT * FROM branches_activites WHERE 1=1";
$params = [];

if (!empty($query)) {
    $sql .= " AND (nom_branche LIKE ? OR localite LIKE ? OR ville LIKE ? OR departement LIKE ? OR region LIKE ? OR activites_en_cours LIKE ?)";
    $like = "%$query%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

if (!empty($regionFilter)) {
    $sql .= " AND region LIKE ?";
    $params[] = "%$regionFilter%";
}

if (!empty($typeFilter)) {
    $sql .= " AND type_entite LIKE ?";
    $params[] = "%$typeFilter%";
}

$sql .= " ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$branches = $stmt->fetchAll();
?>

<!-- En-tête -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Cartographie & Territoires</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;"><?= t('search_title') ?></h1>
        <p style="color:#CBD5E1; max-width:800px; margin:0 auto; font-size:1.1rem;">
            <?= t('search_subtitle') ?>
        </p>
    </div>
</section>

<div class="container" style="padding:50px 24px;">
    <!-- Barre de Recherche Multi-critères -->
    <div class="search-box-card">
        <form method="GET" action="recherche_activites.php" class="search-inputs-grid">
            <!-- Champ Texte -->
            <div class="input-group">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchQueryInput" name="q" value="<?= htmlspecialchars($query) ?>" class="form-control" placeholder="<?= t('search_placeholder') ?>">
            </div>

            <!-- Filtre Région -->
            <div class="input-group">
                <i class="fa-solid fa-map"></i>
                <select id="searchRegionSelect" name="region" class="form-control">
                    <option value="">Toutes les Régions</option>
                    <option value="Lagunes" <?= $regionFilter === 'Lagunes' ? 'selected' : '' ?>>Lagunes (Abidjan)</option>
                    <option value="Gbêkê" <?= $regionFilter === 'Gbêkê' ? 'selected' : '' ?>>Gbêkê (Bouaké)</option>
                    <option value="Bélier" <?= $regionFilter === 'Bélier' ? 'selected' : '' ?>>Bélier (Yamoussoukro)</option>
                    <option value="San-Pédro" <?= $regionFilter === 'San-Pédro' ? 'selected' : '' ?>>San-Pédro</option>
                    <option value="Poro" <?= $regionFilter === 'Poro' ? 'selected' : '' ?>>Poro (Korhogo)</option>
                    <option value="Europe" <?= $regionFilter === 'Europe' ? 'selected' : '' ?>>International (France / Suisse)</option>
                </select>
            </div>

            <!-- Filtre Type d'Entité -->
            <div class="input-group">
                <i class="fa-solid fa-building-flag"></i>
                <select id="searchTypeSelect" name="type" class="form-control">
                    <option value="">Tous les Types</option>
                    <option value="Siège" <?= $typeFilter === 'Siège' ? 'selected' : '' ?>>Siège Central</option>
                    <option value="Filiale" <?= $typeFilter === 'Filiale' ? 'selected' : '' ?>>Filiale</option>
                    <option value="Succursale" <?= $typeFilter === 'Succursale' ? 'selected' : '' ?>>Succursale</option>
                    <option value="Représentation" <?= $typeFilter === 'Représentation' ? 'selected' : '' ?>>Représentation Internationale</option>
                </select>
            </div>

            <!-- Bouton -->
            <button type="submit" class="btn btn-gold">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
        </form>

        <div style="margin-top:16px; display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; color:#64748B;">
            <span><strong id="searchResultCount" style="color:#D97706;"><?= count($branches) ?></strong> représentations et activités répertoriées</span>
            <span><i class="fa-solid fa-bolt" style="color:#10B981;"></i> Recherche dynamique en temps réel active</span>
        </div>
    </div>

    <!-- Message si aucun résultat -->
    <div id="noResultsMessage" style="display: <?= empty($branches) ? 'block' : 'none' ?>; text-align:center; padding:60px 20px; background:#FFFFFF; border-radius:18px; border:1px dashed var(--border-color); margin-bottom:30px;">
        <i class="fa-solid fa-location-slash" style="font-size:3rem; color:#94A3B8; margin-bottom:14px;"></i>
        <h3 style="font-size:1.4rem; color:#0A192F;">Aucune entité trouvée pour ces critères</h3>
        <p style="color:#64748B; font-size:0.95rem; margin-top:6px;">Essayez d'élargir votre recherche ou de réinitialiser les filtres.</p>
        <a href="recherche_activites.php" class="btn btn-outline btn-sm" style="margin-top:14px;">Réinitialiser la recherche</a>
    </div>

    <!-- Grille des Résultats -->
    <div class="branches-grid">
        <?php foreach ($branches as $b): ?>
            <div class="branch-card"
                 data-name="<?= htmlspecialchars($b['nom_branche']) ?>"
                 data-location="<?= htmlspecialchars($b['localite']) ?>"
                 data-city="<?= htmlspecialchars($b['ville']) ?>"
                 data-department="<?= htmlspecialchars($b['departement']) ?>"
                 data-region="<?= htmlspecialchars($b['region']) ?>"
                 data-type="<?= htmlspecialchars($b['type_entite']) ?>"
                 data-activities="<?= htmlspecialchars($b['activites_en_cours']) ?>"
            >
                <div class="branch-header">
                    <span class="branch-type"><?= htmlspecialchars($b['type_entite']) ?></span>
                    <span style="font-size:0.75rem; color:#64748B; font-weight:600;">
                        <i class="fa-solid fa-earth-africa"></i> <?= htmlspecialchars($b['pays']) ?>
                    </span>
                </div>

                <h3 class="branch-name" style="color:#0A192F;"><?= htmlspecialchars($b['nom_branche']) ?></h3>

                <div class="branch-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span><?= htmlspecialchars($b['ville']) ?>, Région <?= htmlspecialchars($b['region']) ?></span>
                </div>

                <p style="font-size:0.82rem; color:#64748B; margin-bottom:12px;">
                    <i class="fa-solid fa-map-pin"></i> <strong>Localité :</strong> <?= htmlspecialchars($b['localite']) ?> (Dép. <?= htmlspecialchars($b['departement']) ?>)<br>
                    <i class="fa-solid fa-building"></i> <strong>Adresse :</strong> <?= htmlspecialchars($b['adresse_physique']) ?>
                </p>

                <div class="branch-activities">
                    <strong style="color:#0A192F; font-size:0.85rem;"><i class="fa-solid fa-calendar-check" style="color:#D97706;"></i> Activités OSHDI dans cette localité :</strong>
                    <p style="margin-top:4px; font-size:0.85rem; color:#334155; line-height:1.5;">
                        <?= htmlspecialchars($b['activites_en_cours']) ?>
                    </p>
                </div>

                <div class="branch-contacts" style="margin-bottom:18px;">
                    <span><i class="fa-solid fa-user-tie" style="color:#D97706;"></i> <strong>Responsable :</strong> <?= htmlspecialchars($b['responsable']) ?></span>
                    <span><i class="fa-solid fa-phone" style="color:#10B981;"></i> <strong>Contact :</strong> <?= htmlspecialchars($b['telephone']) ?></span>
                    <?php if (!empty($b['email'])): ?>
                        <span><i class="fa-solid fa-envelope" style="color:#2563EB;"></i> <?= htmlspecialchars($b['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div style="display:flex; gap:8px;">
                    <a href="souscription.php?ville=<?= urlencode($b['ville']) ?>" class="btn btn-gold btn-sm" style="flex:1;">
                        <i class="fa-solid fa-file-pen"></i> Souscrire ici
                    </a>
                    <a href="tel:<?= htmlspecialchars($b['telephone']) ?>" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-phone"></i> Appeler
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
