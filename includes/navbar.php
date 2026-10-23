<!-- Navbar Principale OSHDI -->
<header class="main-header">
    <div class="navbar-container">
        <!-- Logo & Titre Institutionnel -->
        <a href="index.php" class="brand-logo">
            <img src="assets/images/logo.png" alt="Logo OSHDI">
            <div class="brand-text">
                <h1>OSHDI</h1>
                <span>Œuvres Spirituelles & Humanitaires Divines</span>
            </div>
        </a>

        <!-- Bouton Menu Mobile -->
        <button class="mobile-toggle" aria-label="Menu Mobile">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Menu de Navigation -->
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><?= t('nav_home') ?></a></li>
            <li><a href="programmes.php" class="nav-link"><?= t('nav_programs') ?></a></li>
            <li><a href="formation.php" class="nav-link"><?= t('nav_training') ?></a></li>
            <li><a href="classroom.php" class="nav-link" style="color:#D97706;"><?= t('nav_classroom') ?></a></li>
            <li><a href="souscription.php" class="nav-link"><?= t('nav_subscribe') ?></a></li>
            <li><a href="recherche_activites.php" class="nav-link"><?= t('nav_search') ?></a></li>
            <li><a href="partenaires_espaces.php" class="nav-link"><?= t('nav_partners') ?></a></li>
            <li><a href="mediatheque.php" class="nav-link"><?= t('nav_media') ?></a></li>
            <li>
                <a href="dons.php" class="btn btn-gold btn-sm" style="margin-left: 8px;">
                    <?= t('nav_donate') ?>
                </a>
            </li>
        </ul>
    </div>
</header>
