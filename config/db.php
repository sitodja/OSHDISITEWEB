<?php
// Connexion à la base de données avec fallback automatique SQLite / MySQL

$db_dir = __DIR__ . '/../database';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0777, true);
}

$pdo = null;
$db_driver = 'sqlite';

// Configuration de connexion MySQL WampServer
$use_mysql = true;
$mysql_host = '127.0.0.1';
$mysql_port = '3306';
$mysql_db   = 'oshdi_db';
$mysql_user = 'root';
$mysql_pass = '';

if ($use_mysql) {
    try {
        $pdo = new PDO("mysql:host=$mysql_host;port=$mysql_port;dbname=$mysql_db;charset=utf8mb4", $mysql_user, $mysql_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $db_driver = 'mysql';
    } catch (Exception $e) {
        $pdo = null;
    }
}

// Fallback SQLite ultra-rapide et fiable
if (!$pdo) {
    $sqlite_file = $db_dir . '/oshdi.sqlite';
    $need_init = !file_exists($sqlite_file) || filesize($sqlite_file) < 100;
    
    try {
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db_driver = 'sqlite';
        
        if ($need_init) {
            init_database_schema($pdo);
        }
    } catch (Exception $e) {
        die("Erreur de connexion base de données : " . $e->getMessage());
    }
}

// Vérification de la table transactions_paiements (création transparente si absente)
if ($pdo) {
    try {
        $idType = ($db_driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $pdo->exec("CREATE TABLE IF NOT EXISTS transactions_paiements (
            id $idType,
            reference VARCHAR(100) UNIQUE NOT NULL,
            type_commande VARCHAR(50) NOT NULL,
            item_id INTEGER DEFAULT 0,
            item_title VARCHAR(255),
            montant DECIMAL(12,2) NOT NULL,
            devise VARCHAR(10) DEFAULT 'FCFA',
            moyen_paiement VARCHAR(50) NOT NULL,
            statut VARCHAR(30) DEFAULT 'en_attente',
            passerelle_ref VARCHAR(255),
            donateur_nom VARCHAR(150),
            donateur_email VARCHAR(150),
            donateur_telephone VARCHAR(50),
            crypto_devise VARCHAR(20),
            crypto_montant DECIMAL(18,8),
            crypto_wallet VARCHAR(255),
            crypto_tx_hash VARCHAR(255),
            details_requete TEXT,
            details_reponse TEXT,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_validation DATETIME
        )");
    } catch (Exception $e) {
        // Table déjà existante ou erreur non bloquante
    }
}

// Fonction d'initialisation des tables et données de démonstration
function init_database_schema($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(100) NOT NULL,
        prenoms VARCHAR(150) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        telephone VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'membre',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS formations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titre VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        duree VARCHAR(50) NOT NULL,
        formateur VARCHAR(100) NOT NULL,
        video_url VARCHAR(255) NOT NULL,
        pdf_title VARCHAR(200) NOT NULL,
        pdf_summary TEXT NOT NULL,
        image_url VARCHAR(255) DEFAULT 'assets/images/hero_banner.jpg'
    );

    CREATE TABLE IF NOT EXISTS inscriptions_formation (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        formation_id INTEGER NOT NULL,
        montant_paye DECIMAL(10,2) NOT NULL,
        moyen_paiement VARCHAR(50) NOT NULL,
        reference_transaction VARCHAR(100) NOT NULL,
        statut VARCHAR(20) DEFAULT 'valide',
        date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS souscriptions_sondage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_complet VARCHAR(200) NOT NULL,
        email VARCHAR(150) NOT NULL,
        telephone VARCHAR(50) NOT NULL,
        profession VARCHAR(100) NOT NULL,
        localite VARCHAR(100) NOT NULL,
        ville VARCHAR(100) NOT NULL,
        region VARCHAR(100) NOT NULL,
        programme_choisi VARCHAR(100) NOT NULL,
        besoins_assistance TEXT NOT NULL,
        montant_paye DECIMAL(10,2) NOT NULL,
        moyen_paiement VARCHAR(50) NOT NULL,
        reference_transaction VARCHAR(100) NOT NULL,
        signature_electronique VARCHAR(100) NOT NULL,
        statut VARCHAR(30) DEFAULT 'transmis_oshdi',
        date_souscription DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS branches_activites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_branche VARCHAR(150) NOT NULL,
        type_entite VARCHAR(50) NOT NULL, /* Filiale, Succursale, Représentation */
        localite VARCHAR(100) NOT NULL,
        ville VARCHAR(100) NOT NULL,
        departement VARCHAR(100) NOT NULL,
        region VARCHAR(100) NOT NULL,
        pays VARCHAR(100) DEFAULT 'Côte d''Ivoire',
        responsable VARCHAR(150) NOT NULL,
        telephone VARCHAR(50) NOT NULL,
        email VARCHAR(100),
        activites_en_cours TEXT NOT NULL,
        adresse_physique TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS espaces_partenaires (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_entreprise VARCHAR(150) NOT NULL,
        type_activite VARCHAR(100) NOT NULL, /* Boutique physique, Boutique en ligne, Agence com, etc. */
        description TEXT NOT NULL,
        site_web VARCHAR(200),
        contact VARCHAR(100) NOT NULL,
        logo_url VARCHAR(255),
        pack_type VARCHAR(50) NOT NULL, /* Standard, Premium, Sponsor */
        montant_paye DECIMAL(10,2) NOT NULL,
        moyen_paiement VARCHAR(50) NOT NULL,
        statut VARCHAR(20) DEFAULT 'actif',
        date_debut DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS dons_offrandes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        donateur_nom VARCHAR(150) NOT NULL,
        donateur_email VARCHAR(150),
        donateur_telephone VARCHAR(50) NOT NULL,
        type_don VARCHAR(100) NOT NULL, /* Dîme, Offrande, Don Charité, REMDIS, etc. */
        montant DECIMAL(12,2) NOT NULL,
        devise VARCHAR(10) DEFAULT 'FCFA',
        moyen_paiement VARCHAR(50) NOT NULL,
        reference_recu VARCHAR(100) UNIQUE NOT NULL,
        message_intention TEXT,
        date_don DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS mediatheque (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titre VARCHAR(200) NOT NULL,
        type_media VARCHAR(20) NOT NULL, /* image, video */
        programme VARCHAR(50) NOT NULL, /* ABM, CSD, JORESC, LEADER, ODH, CHARITE, REMDIS */
        annee VARCHAR(10) NOT NULL,
        description TEXT,
        media_url VARCHAR(255) NOT NULL,
        miniature_url VARCHAR(255)
    );
    ";

    $pdo->exec($sql);

    // Données de semence (Seed data)
    seed_initial_data($pdo);
}

function seed_initial_data($pdo) {
    // 1. Formations
    $stmt = $pdo->prepare("INSERT INTO formations (titre, description, categorie, prix, duree, formateur, video_url, pdf_title, pdf_summary, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $formations = [
        [
            'Fondements de la Délivrance & Restauration Spirituelle',
            'Comprendre les causes spirituelles des blocages de vie, les lois de la prière efficace et le protocole divin de réhabilitation des vies brisées.',
            'Spiritualité & Délivrance',
            15000,
            '6 Modules - 12 Heures',
            'Équipe Pastorale & Intercession OSHDI',
            'assets/videos/demo_formation.mp4',
            'Manuel Protocolaire de Restauration des Vies Brisées (OSHDI)',
            'Guide de prières d\'autorité, versets de bénédiction, brisement des liens ancestraux et réhabilitation par la foi vivante.',
            'assets/images/hero_banner.jpg'
        ],
        [
            'Entrepreneuriat Culinaire & Gestion de Restaurant (Spécial ABM)',
            'Formation complète réservée aux candidats et lauréats Africa Best Menu pour lancer, gérer et rentabiliser leur établissement gastronomique.',
            'Entrepreneuriat & Métiers',
            25000,
            '8 Modules - 20 Heures',
            'Chef Consultant & Expert ABM',
            'assets/videos/demo_formation.mp4',
            'Guide Pratique du Restaurateur OSHDI & Hygiène HACCP',
            'Calcul des coûts de revient, maîtrise des marges, recettes signatures et fiches techniques certifiées ABM.',
            'assets/images/hero_banner.jpg'
        ],
        [
            'Leadership Éthique & Réussite Scolaire (Leader Universel)',
            'Méthodes de travail intellectuel d\'élite, gestion du temps, mémoire et développement du caractère pour les futurs bâtisseurs de la nation.',
            'Leadership & Éducation',
            10000,
            '4 Modules - 8 Heures',
            'Dr. K. N\'Guessan - Mentorat Leader Universel',
            'assets/videos/demo_formation.mp4',
            'Cahier d\'Excellence et de Stratégie Académique',
            'Fiches de révision, techniques mnémoniques, planification des examens et code d\'honneur de l\'élève modèle.',
            'assets/images/hero_banner.jpg'
        ],
        [
            'Soins d\'Assistance Communautaire & Premiers Secours (REMDIS)',
            'Compétences essentielles pour les secouristes bénévoles et aidants dans les centres de santé et les communautés démunies.',
            'Santé & Social',
            12000,
            '5 Modules - 10 Heures',
            'Dr. H. Kouassi - Réseau Médical REMDIS',
            'assets/videos/demo_formation.mp4',
            'Protocole Sanitaire Communautaire & Guide de Prise en Charge',
            'Gestes qui sauvent, orientation vers les cliniques partenaires REMDIS et délivrance des bons de soins.',
            'assets/images/hero_banner.jpg'
        ]
    ];

    foreach ($formations as $f) {
        $stmt->execute($f);
    }

    // 2. Représentations et filiales OSHDI
    $stmtBranches = $pdo->prepare("INSERT INTO branches_activites (nom_branche, type_entite, localite, ville, departement, region, pays, responsable, telephone, email, activites_en_cours, adresse_physique) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $branches = [
        [
            'Siège International OSHDI - Abidjan',
            'Siège Central & Coordination',
            'Cocody Riviera Palmeraie',
            'Abidjan',
            'Abidjan',
            'Lagunes',
            'Côte d\'Ivoire',
            'Révérend Coordinateur Général',
            '+225 07 10 20 30 40',
            'siege@oshdi.org',
            'Coordination générale, Célébrations CSD hebdomadaires, auditions ABM, inscriptions au sondage JORESC et centre de distribution OSHDI Charité.',
            'Boulevard Mitterrand, Carrefour Rosiers, Bâtiment OSHDI'
        ],
        [
            'Représentation Régionale Bouaké - Gbêkê',
            'Filiale Régionale',
            'Commerce / Kennedy',
            'Bouaké',
            'Bouaké',
            'Gbêkê',
            'Côte d\'Ivoire',
            'Pasteur D. Traoré',
            '+225 05 22 33 44 55',
            'bouake@oshdi.org',
            'Campagne de dépistage REMDIS, distribution de kits alimentaires aux veuves, présélection Leader Universel.',
            'Rue des Banques, face Complexe Municipal'
        ],
        [
            'Antenne San-Pédro - Bas-Sassandra',
            'Succursale',
            'Bardot / Cité',
            'San-Pédro',
            'San-Pédro',
            'San-Pédro',
            'Côte d\'Ivoire',
            'Mme E. Bahi (Déléguée Sociale)',
            '+225 01 44 55 66 77',
            'sanpedro@oshdi.org',
            'Sondage d\'opinion JORESC sur les familles de pêcheurs vulnérables, préparatifs des mariages collectifs ODH.',
            'Quartier Lac, Carrefour CHR'
        ],
        [
            'Représentation de Yamoussoukro - Bélier',
            'Succursale Régionale',
            'Habitat / Assabou',
            'Yamoussoukro',
            'Yamoussoukro',
            'Bélier',
            'Côte d\'Ivoire',
            'Ancien Évangéliste Yao',
            '+225 07 88 99 00 11',
            'yakro@oshdi.org',
            'Permanence d\'écoute et prières pour les malades du CHR, remise des prix Leader Universel session régionale.',
            'Immeuble de la Paix, niveau 1'
        ],
        [
            'Délégation Korhogo - Poro',
            'Filiale Grand-Nord',
            'Soba / Petit Paris',
            'Korhogo',
            'Korhogo',
            'Poro',
            'Côte d\'Ivoire',
            'Missionnaire Coulibaly',
            '+225 05 77 66 55 44',
            'korhogo@oshdi.org',
            'Projet eau potable OSHDI Charité, assistance aux orphelinats, permanences médicales REMDIS.',
            'Avenue Félix Houphouët Boigny'
        ],
        [
            'Bureau de Liaison Europe - Paris / Genève',
            'Représentation Internationale',
            'Châtelet / Île-de-France',
            'Paris',
            'Paris (75)',
            'Île-de-France',
            'France / Europe',
            'M. Jean-Paul Meyer',
            '+33 6 12 34 56 78',
            'europe@oshdi.org',
            'Mobilisation des donateurs de la diaspora, partenariats médicaux internationaux REMDIS, diffusion des cours en ligne.',
            '45 Rue de Rivoli, 75001 Paris'
        ]
    ];

    foreach ($branches as $b) {
        $stmtBranches->execute($b);
    }

    // 3. Espaces partenaires initiaux
    $stmtPart = $pdo->prepare("INSERT INTO espaces_partenaires (nom_entreprise, type_activite, description, site_web, contact, logo_url, pack_type, montant_paye, moyen_paiement) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $partenaires = [
        [
            'Clinique Sainte Miséricorde',
            'Établissement Hospitalier Partenaire REMDIS',
            'Partenaire officiel du réseau REMDIS pour les bilans de santé, consultations spécialisées et accouchements à coûts solidaires.',
            'https://clinique-sainte-misericorde.com',
            '+225 07 45 67 89',
            'assets/images/logo.jpg',
            'Pack Partenaire Santé',
            150000,
            'Orange Money'
        ],
        [
            'Saveurs d\'Afrique Épices & Restauration',
            'Boutique Gastronomique & Matériel ABM',
            'Fournisseur officiel des équipements de cuisine et des ingrédients bio pour les compétitions Africa Best Menu.',
            'https://saveurs-afrique.com',
            '+225 05 12 34 56',
            'assets/images/logo.jpg',
            'Pack Sponsor Gastronomie',
            100000,
            'Wave'
        ],
        [
            'Librairie & Éditions La Renaissance Divine',
            'Librairie Chrétienne & Manuels Scolaires',
            'Fourniture des manuels pour les lauréats Leader Universel et des bibles d\'études pour les programmes OSHDI.',
            'https://librairie-renaissance.com',
            '+225 01 98 76 54',
            'assets/images/logo.jpg',
            'Pack Visibilité Entreprise',
            75000,
            'MTN Money'
        ]
    ];

    foreach ($partenaires as $p) {
        $stmtPart->execute($p);
    }

    // 4. Utilisateur admin par défaut
    $stmtUser = $pdo->prepare("INSERT INTO users (nom, prenoms, email, telephone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtUser->execute([
        'Administrateur',
        'OSHDI',
        'admin@oshdi.org',
        '+225 07 00 00 00 00',
        password_hash('oshdi2026', PASSWORD_DEFAULT),
        'admin'
    ]);
}
