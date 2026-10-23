-- ====================================================================
-- BASE DE DONNÉES OFFICIELLE : OSHDI (Œuvres Spirituelles et Humanitaires Divines)
-- Compatible WampServer (MySQL 5.7+ / MySQL 8.0+ / MariaDB 10+)
-- Encodage : UTF-8 Unicode (utf8mb4)
-- Date de génération : 2026-10-14
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. CRÉATION ET SÉLECTION DE LA BASE DE DONNÉES
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `oshdi_db` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `oshdi_db`;

-- --------------------------------------------------------
-- 2. STRUCTURE DES TABLES
-- --------------------------------------------------------

-- Table 1 : Utilisateurs & Membres
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenoms` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `telephone` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'membre',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2 : Formations en Ligne (Cours, Vidéos & PDF Protégés)
DROP TABLE IF EXISTS `formations`;
CREATE TABLE `formations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titre` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `categorie` VARCHAR(50) NOT NULL,
    `prix` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `duree` VARCHAR(50) NOT NULL,
    `formateur` VARCHAR(100) NOT NULL,
    `video_url` VARCHAR(255) NOT NULL,
    `pdf_title` VARCHAR(200) NOT NULL,
    `pdf_summary` TEXT NOT NULL,
    `image_url` VARCHAR(255) DEFAULT 'assets/images/hero_banner.jpg',
    `statut` VARCHAR(20) DEFAULT 'actif',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_formations_categorie` (`categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3 : Inscriptions & Accès aux Formations
DROP TABLE IF EXISTS `inscriptions_formation`;
CREATE TABLE `inscriptions_formation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `formation_id` INT NOT NULL,
    `montant_paye` DECIMAL(10,2) NOT NULL,
    `moyen_paiement` VARCHAR(50) NOT NULL,
    `reference_transaction` VARCHAR(100) UNIQUE NOT NULL,
    `statut` VARCHAR(20) DEFAULT 'valide',
    `date_paiement` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_inscriptions_user` (`user_id`),
    INDEX `idx_inscriptions_formation` (`formation_id`),
    INDEX `idx_inscriptions_ref` (`reference_transaction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4 : Souscriptions & Bordereaux de Sondage d'Opinion
DROP TABLE IF EXISTS `souscriptions_sondage`;
CREATE TABLE `souscriptions_sondage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_complet` VARCHAR(200) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `telephone` VARCHAR(50) NOT NULL,
    `profession` VARCHAR(100) NOT NULL,
    `localite` VARCHAR(100) NOT NULL,
    `ville` VARCHAR(100) NOT NULL,
    `region` VARCHAR(100) NOT NULL,
    `programme_choisi` VARCHAR(150) NOT NULL,
    `besoins_assistance` TEXT NOT NULL,
    `montant_paye` DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    `moyen_paiement` VARCHAR(50) NOT NULL,
    `reference_transaction` VARCHAR(100) UNIQUE NOT NULL,
    `signature_electronique` VARCHAR(150) NOT NULL,
    `statut` VARCHAR(30) DEFAULT 'transmis_oshdi',
    `date_souscription` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_souscriptions_ville` (`ville`),
    INDEX `idx_souscriptions_region` (`region`),
    INDEX `idx_souscriptions_programme` (`programme_choisi`),
    INDEX `idx_souscriptions_ref` (`reference_transaction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 5 : Filiales, Succursales & Représentations Régionales (Moteur de Recherche)
DROP TABLE IF EXISTS `branches_activites`;
CREATE TABLE `branches_activites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_branche` VARCHAR(150) NOT NULL,
    `type_entite` VARCHAR(50) NOT NULL, -- Siège, Filiale, Succursale, Représentation
    `localite` VARCHAR(100) NOT NULL,
    `ville` VARCHAR(100) NOT NULL,
    `departement` VARCHAR(100) NOT NULL,
    `region` VARCHAR(100) NOT NULL,
    `pays` VARCHAR(100) DEFAULT "Côte d'Ivoire",
    `responsable` VARCHAR(150) NOT NULL,
    `telephone` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100),
    `activites_en_cours` TEXT NOT NULL,
    `adresse_physique` TEXT NOT NULL,
    INDEX `idx_branches_ville` (`ville`),
    INDEX `idx_branches_region` (`region`),
    INDEX `idx_branches_type` (`type_entite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 6 : Espaces Publicitaires & Partenaires Payants
DROP TABLE IF EXISTS `espaces_partenaires`;
CREATE TABLE `espaces_partenaires` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_entreprise` VARCHAR(150) NOT NULL,
    `type_activite` VARCHAR(100) NOT NULL, -- Boutique physique, Boutique en ligne, Agence com, Clinique...
    `description` TEXT NOT NULL,
    `site_web` VARCHAR(255),
    `contact` VARCHAR(100) NOT NULL,
    `logo_url` VARCHAR(255) DEFAULT 'assets/images/logo.jpg',
    `pack_type` VARCHAR(50) NOT NULL, -- Pack Visibilité, Pack Entreprise, Pack Sponsor
    `montant_paye` DECIMAL(10,2) NOT NULL,
    `moyen_paiement` VARCHAR(50) NOT NULL,
    `statut` VARCHAR(20) DEFAULT 'actif',
    `date_debut` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_partenaires_statut` (`statut`),
    INDEX `idx_partenaires_pack` (`pack_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 7 : Dons, Offrandes & Dîmes en Ligne
DROP TABLE IF EXISTS `dons_offrandes`;
CREATE TABLE `dons_offrandes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `donateur_nom` VARCHAR(150) NOT NULL,
    `donateur_email` VARCHAR(150),
    `donateur_telephone` VARCHAR(50) NOT NULL,
    `type_don` VARCHAR(100) NOT NULL, -- Offrande Souveraineté, Dîme, Don Charité, REMDIS...
    `montant` DECIMAL(12,2) NOT NULL,
    `devise` VARCHAR(10) DEFAULT 'FCFA',
    `moyen_paiement` VARCHAR(50) NOT NULL,
    `reference_recu` VARCHAR(100) UNIQUE NOT NULL,
    `message_intention` TEXT,
    `date_don` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dons_ref` (`reference_recu`),
    INDEX `idx_dons_date` (`date_don`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 8 : Médiathèque des Évènements (Passés & Présents)
DROP TABLE IF EXISTS `mediatheque`;
CREATE TABLE `mediatheque` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titre` VARCHAR(200) NOT NULL,
    `type_media` VARCHAR(20) NOT NULL, -- video, image
    `programme` VARCHAR(50) NOT NULL, -- ABM, CSD, JORESC, LEADER, ODH, CHARITE, REMDIS
    `annee` VARCHAR(10) NOT NULL,
    `lieu` VARCHAR(150),
    `description` TEXT,
    `media_url` VARCHAR(255) NOT NULL,
    `miniature_url` VARCHAR(255),
    INDEX `idx_media_programme` (`programme`),
    INDEX `idx_media_type` (`type_media`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. JEU DE DONNÉES INITIALES (SEED DATA)
-- --------------------------------------------------------

-- A. Utilisateurs Initiaux (Admin & Membres)
-- Mot de passe par défaut : oshdi2026 (haché via bcrypt)
INSERT INTO `users` (`id`, `nom`, `prenoms`, `email`, `telephone`, `password`, `role`) VALUES
(1, 'Administrateur', 'Général OSHDI', 'admin@oshdi.org', '+225 07 00 00 00 00', '$2y$10$perbxuwPyNn.pOdPVJOE9echFQAehpfLYIx57Dua8exXL0doF0KEe', 'admin'),
(2, 'Kouadio', 'Paul Emmanuel', 'paul.kouadio@gmail.com', '+225 07 12 34 56 78', '$2y$10$perbxuwPyNn.pOdPVJOE9echFQAehpfLYIx57Dua8exXL0doF0KEe', 'membre'),
(3, 'Marie', 'Aimée Grâce', 'marie.aimee@gmail.com', '+225 05 98 76 54 32', '$2y$10$perbxuwPyNn.pOdPVJOE9echFQAehpfLYIx57Dua8exXL0doF0KEe', 'membre');

-- B. Formations en Ligne (Supports Protégés Lecture Seule)
INSERT INTO `formations` (`id`, `titre`, `description`, `categorie`, `prix`, `duree`, `formateur`, `video_url`, `pdf_title`, `pdf_summary`, `image_url`) VALUES
(1, 'Fondements de la Délivrance & Restauration Spirituelle', 'Comprendre les causes spirituelles des blocages de vie, les lois de la prière efficace et le protocole divin de réhabilitation des vies brisées.', 'Spiritualité & Délivrance', 15000.00, '6 Modules - 12 Heures', 'Collège Pastoral & Intercession OSHDI', 'assets/videos/demo_formation.mp4', 'Manuel Protocolaire de Restauration des Vies Brisées (OSHDI)', 'Guide de prières d\'autorité, versets de bénédiction, brisement des liens ancestraux et réhabilitation par la foi vivante.', 'assets/images/hero_banner.jpg'),
(2, 'Entrepreneuriat Culinaire & Gestion de Restaurant (Spécial ABM)', 'Formation complète réservée aux candidats et lauréats Africa Best Menu pour lancer, gérer et rentabiliser leur établissement gastronomique.', 'Entrepreneuriat & Métiers', 25000.00, '8 Modules - 20 Heures', 'Chef Consultant & Expert ABM', 'assets/videos/demo_formation.mp4', 'Guide Pratique du Restaurateur OSHDI & Hygiène HACCP', 'Calcul des coûts de revient, maîtrise des marges, recettes signatures et fiches techniques certifiées ABM.', 'assets/images/hero_banner.jpg'),
(3, 'Leadership Éthique & Réussite Scolaire (Leader Universel)', 'Méthodes de travail intellectuel d\'élite, gestion du temps, mémoire et développement du caractère pour les futurs bâtisseurs de la nation.', 'Leadership & Éducation', 10000.00, '4 Modules - 8 Heures', 'Dr. K. N\'Guessan - Mentorat Leader Universel', 'assets/videos/demo_formation.mp4', 'Cahier d\'Excellence et de Stratégie Académique', 'Fiches de révision, techniques mnémoniques, planification des examens et code d\'honneur de l\'élève modèle.', 'assets/images/hero_banner.jpg'),
(4, 'Soins d\'Assistance Communautaire & Premiers Secours (REMDIS)', 'Compétences essentielles pour les secouristes bénévoles et aidants dans les centres de santé et les communautés démunies.', 'Santé & Social', 12000.00, '5 Modules - 10 Heures', 'Dr. H. Kouassi - Réseau Médical REMDIS', 'assets/videos/demo_formation.mp4', 'Protocole Sanitaire Communautaire & Guide de Prise en Charge', 'Gestes qui sauvent, orientation vers les cliniques partenaires REMDIS et délivrance des bons de soins.', 'assets/images/hero_banner.jpg');

-- C. Filiales, Succursales et Représentations OSHDI (Régions & Villes)
INSERT INTO `branches_activites` (`id`, `nom_branche`, `type_entite`, `localite`, `ville`, `departement`, `region`, `pays`, `responsable`, `telephone`, `email`, `activites_en_cours`, `adresse_physique`) VALUES
(1, 'Siège International OSHDI - Abidjan', 'Siège Central', 'Cocody Riviera Palmeraie', 'Abidjan', 'Abidjan', 'Lagunes', "Côte d'Ivoire", 'Révérend Coordinateur Général', '+225 07 10 20 30 40', 'siege@oshdi.org', 'Coordination générale, Célébrations CSD hebdomadaires, auditions du concours ABM, inscriptions au sondage JORESC et centre de distribution OSHDI Charité.', 'Boulevard Mitterrand, Carrefour Rosiers, Bâtiment OSHDI'),
(2, 'Représentation Régionale Bouaké - Gbêkê', 'Filiale', 'Commerce / Kennedy', 'Bouaké', 'Bouaké', 'Gbêkê', "Côte d'Ivoire", 'Pasteur D. Traoré', '+225 05 22 33 44 55', 'bouake@oshdi.org', 'Campagne de dépistage REMDIS, distribution de kits alimentaires aux veuves, présélection Leader Universel et prières d\'intercession.', 'Rue des Banques, face Complexe Municipal'),
(3, 'Antenne San-Pédro - Bas-Sassandra', 'Succursale', 'Bardot / Cité', 'San-Pédro', 'San-Pédro', 'San-Pédro', "Côte d'Ivoire", 'Mme E. Bahi (Déléguée Sociale)', '+225 01 44 55 66 77', 'sanpedro@oshdi.org', 'Sondage d\'opinion JORESC sur les familles de pêcheurs vulnérables, préparatifs des mariages collectifs ODH et visites aux malades du CHR.', 'Quartier Lac, Carrefour CHR'),
(4, 'Représentation de Yamoussoukro - Bélier', 'Succursale', 'Habitat / Assabou', 'Yamoussoukro', 'Yamoussoukro', 'Bélier', "Côte d'Ivoire", 'Ancien Évangéliste Yao', '+225 07 88 99 00 11', 'yakro@oshdi.org', 'Permanence d\'écoute et prières pour les malades, remise des prix Leader Universel session régionale et consultations REMDIS.', 'Immeuble de la Paix, niveau 1'),
(5, 'Délégation Korhogo - Poro', 'Filiale', 'Soba / Petit Paris', 'Korhogo', 'Korhogo', 'Poro', "Côte d'Ivoire", 'Missionnaire Coulibaly', '+225 05 77 66 55 44', 'korhogo@oshdi.org', 'Projet eau potable OSHDI Charité, assistance aux orphelinats, permanences médicales du réseau REMDIS.', 'Avenue Félix Houphouët Boigny'),
(6, 'Bureau de Liaison Europe - Paris / Genève', 'Représentation', 'Châtelet / Île-de-France', 'Paris', 'Paris (75)', 'Île-de-France', 'France / Europe', 'M. Jean-Paul Meyer', '+33 6 12 34 56 78', 'europe@oshdi.org', 'Mobilisation des donateurs de la diaspora, partenariats médicaux internationaux REMDIS, diffusion des cours en ligne et dons.', '45 Rue de Rivoli, 75001 Paris');

-- D. Espaces Partenaires & Annonceurs Référencés
INSERT INTO `espaces_partenaires` (`id`, `nom_entreprise`, `type_activite`, `description`, `site_web`, `contact`, `logo_url`, `pack_type`, `montant_paye`, `moyen_paiement`, `statut`) VALUES
(1, 'Clinique Sainte Miséricorde', 'Établissement Hospitalier Partenaire REMDIS', 'Partenaire officiel du réseau REMDIS pour les bilans de santé, consultations spécialisées et accouchements à coûts solidaires.', 'https://clinique-sainte-misericorde.com', '+225 07 45 67 89', 'assets/images/logo.jpg', 'Pack Entreprise & Agence', 150000.00, 'Orange Money', 'actif'),
(2, 'Saveurs d\'Afrique Épices & Restauration', 'Boutique Gastronomique & Matériel ABM', 'Fournisseur officiel des équipements de cuisine et des ingrédients bio pour les compétitions Africa Best Menu.', 'https://saveurs-afrique.com', '+225 05 12 34 56', 'assets/images/logo.jpg', 'Pack Sponsor Évènements', 250000.00, 'Wave Money', 'actif'),
(3, 'Librairie & Éditions La Renaissance Divine', 'Librairie Chrétienne & Manuels Scolaires', 'Fourniture des manuels pour les lauréats Leader Universel et des bibles d\'études pour les programmes OSHDI.', 'https://librairie-renaissance.com', '+225 01 98 76 54', 'assets/images/logo.jpg', 'Pack Visibilité Boutique', 50000.00, 'MTN Mobile Money', 'actif');

-- E. Exemples de Souscriptions & Bordereaux de Sondage d'Opinion
INSERT INTO `souscriptions_sondage` (`id`, `nom_complet`, `email`, `telephone`, `profession`, `localite`, `ville`, `region`, `programme_choisi`, `besoins_assistance`, `montant_paye`, `moyen_paiement`, `reference_transaction`, `signature_electronique`, `statut`) VALUES
(1, 'Kouamé N\'Dri Paul', 'paul.kouame@gmail.com', '+225 07 48 59 60 71', 'Commerçant', 'Yopougon Selmer', 'Abidjan', 'Lagunes', 'REMDIS - Prise en charge médicale solidaire', 'Demande d\'appui médical pour intervention chirurgicale ophtalmologique dans une clinique partenaire agréée REMDIS.', 5000.00, 'Wave Money', 'OSHDI-SUB-20261014-482910', 'Kouamé N\'Dri Paul', 'transmis_oshdi'),
(2, 'Konan Affoué Bernadette', 'affoue.konan@yahoo.fr', '+225 05 11 22 33 44', 'Cuisinière Traiteur', 'Air France 1', 'Bouaké', 'Gbêkê', 'ABM - Concours Africa Best Menu', 'Candidature au concours culinaire ABM afin de solliciter la dotation d\'installation d\'un restaurant solidaire.', 5000.00, 'Orange Money', 'OSHDI-SUB-20261014-991823', 'Konan Affoué Bernadette', 'transmis_oshdi');

-- F. Dons et Offrandes en Ligne avec Reçus Officiels
INSERT INTO `dons_offrandes` (`id`, `donateur_nom`, `donateur_email`, `donateur_telephone`, `type_don`, `montant`, `devise`, `moyen_paiement`, `reference_recu`, `message_intention`) VALUES
(1, 'Famille Bamba', 'famille.bamba@gmail.com', '+225 07 88 77 66 55', 'Offrande de Souveraineté de Dieu (CSD)', 50000.00, 'FCFA', 'Wave Money', 'OSHDI-DON-20261014-774912', 'En action de grâce pour la guérison de notre enfant et le soutien des orphelins.'),
(2, 'Docteur Touré Amadou', 'toure.amadou@chu.ci', '+225 05 44 33 22 11', 'Soutien Réseau Médical REMDIS', 100000.00, 'FCFA', 'Carte Bancaire (Visa)', 'OSHDI-DON-20261014-389102', 'Participation au fonds de solidarité chirurgicale pour les indigents.');

-- G. Médiathèque des Évènements (Passés & Présents)
INSERT INTO `mediatheque` (`id`, `titre`, `type_media`, `programme`, `annee`, `lieu`, `description`, `media_url`, `miniature_url`) VALUES
(1, 'Grande Finale Africa Best Menu (ABM) - Édition 2026', 'video', 'ABM', '2026', 'Palais de la Culture, Abidjan', 'Couronnement des 3 meilleurs chefs restaurateurs avec remise des chèques d\'installation et équipements professionnels.', 'assets/videos/demo_formation.mp4', 'assets/images/hero_banner.jpg'),
(2, 'Nuit de la Célébration de la Souveraineté de Dieu (CSD)', 'video', 'CSD', '2026', 'Stade Municipal de Bouaké', 'Rassemblement de plus de 15 000 personnes dans la louange, l\'intercession et la prière de guérison divine.', 'assets/videos/demo_formation.mp4', 'assets/images/hero_banner.jpg'),
(3, 'Journée Portes Ouvertes JORESC & Prise en Charge Médicale', 'image', 'JORESC', '2026', 'Yamoussoukro, Quartier Habitat', 'Dépouillement des bordereaux de sondage d\'opinion et délivrance des bons de soins REMDIS aux familles.', 'assets/images/hero_banner.jpg', 'assets/images/hero_banner.jpg'),
(4, 'Gala d\'Excellence Leader Universel : 300 Bourses Scolaires', 'image', 'LEADER', '2026', 'Hôtel Ivoire, Abidjan', 'Cérémonie solennelle récompensant les meilleurs élèves orphelins et défavorisés de Côte d\'Ivoire.', 'assets/images/hero_banner.jpg', 'assets/images/hero_banner.jpg'),
(5, 'Célébration des 50 Mariages Collectifs (ODH)', 'video', 'ODH', '2026', 'San-Pédro', 'Restauration de la dignité de 50 couples dans la grâce civile et divine avec banquet festif offert.', 'assets/videos/demo_formation.mp4', 'assets/images/hero_banner.jpg'),
(6, 'Mission Humanitaire OSHDI CHARITÉ : Maison d\'Arrêt & Hôpitaux', 'image', 'CHARITE', '2026', 'MACA & CHU de Treichville', 'Distribution de 10 tonnes de vivres, kits d\'hygiène et soutien moral et spirituel aux détenus et malades.', 'assets/images/hero_banner.jpg', 'assets/images/hero_banner.jpg');

-- ========================================================
-- 4. MODULE VISIOCONFÉRENCE & CLASSE VIRTUELLE (OSHDI VISIOPRO)
-- ========================================================

-- Table 9 : Réunions et Visioconférences
DROP TABLE IF EXISTS `classroom_meetings`;
CREATE TABLE `classroom_meetings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) UNIQUE NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `host_user_id` INT DEFAULT NULL,
    `host_name` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `is_locked` TINYINT(1) DEFAULT 0,
    `require_waiting_room` TINYINT(1) DEFAULT 1,
    `allow_screen_share` TINYINT(1) DEFAULT 1,
    `allow_chat` TINYINT(1) DEFAULT 1,
    `max_participants` INT DEFAULT 50,
    `scheduled_start` DATETIME DEFAULT NULL,
    `scheduled_end` DATETIME DEFAULT NULL,
    `recurrence` VARCHAR(20) DEFAULT 'none',
    `timezone` VARCHAR(50) DEFAULT 'Africa/Abidjan',
    `reminder_minutes` INT DEFAULT 15,
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_meeting_code` (`meeting_code`),
    INDEX `idx_meeting_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 10 : Émargement & Historique de Présence des Participants
DROP TABLE IF EXISTS `classroom_meeting_participants`;
CREATE TABLE `classroom_meeting_participants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'participant',
    `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `left_at` DATETIME DEFAULT NULL,
    `duration_seconds` INT DEFAULT 0,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_participant_code` (`meeting_code`),
    INDEX `idx_participant_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 11 : Invitations Sécurisées aux Réunions
DROP TABLE IF EXISTS `classroom_invitations`;
CREATE TABLE `classroom_invitations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `invitation_token` VARCHAR(64) UNIQUE NOT NULL,
    `recipient_email` VARCHAR(150) DEFAULT NULL,
    `recipient_name` VARCHAR(100) DEFAULT NULL,
    `role` VARCHAR(20) DEFAULT 'participant',
    `status` VARCHAR(20) DEFAULT 'pending',
    `expires_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_invite_token` (`invitation_token`),
    INDEX `idx_invite_code` (`meeting_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 12 : Journal d'Audit & Sécurité de Modération
DROP TABLE IF EXISTS `classroom_audit_logs`;
CREATE TABLE `classroom_audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_meeting` (`meeting_code`),
    INDEX `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 13 : Messages du Chat & Notes Vocales
DROP TABLE IF EXISTS `classroom_messages`;
CREATE TABLE `classroom_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` VARCHAR(50) DEFAULT 'OSHDI-ROOM-702',
    `user_name` VARCHAR(100) NOT NULL,
    `message` TEXT,
    `msg_type` VARCHAR(20) DEFAULT 'text',
    `audio_url` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_chat_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 14 : Sondage d'Intercession en Direct
DROP TABLE IF EXISTS `classroom_poll_votes`;
CREATE TABLE `classroom_poll_votes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` VARCHAR(50) DEFAULT 'OSHDI-ROOM-702',
    `poll_id` INT DEFAULT 1,
    `user_identifier` VARCHAR(100) NOT NULL,
    `option_index` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_poll_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 15 : Salle d'Attente & Autorisations d'Accès
DROP TABLE IF EXISTS `classroom_permissions`;
CREATE TABLE `classroom_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` VARCHAR(50) DEFAULT 'OSHDI-ROOM-702',
    `user_uid` VARCHAR(100) NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `user_location` VARCHAR(100) DEFAULT '',
    `status` VARCHAR(20) DEFAULT 'pending',
    `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_perm_room` (`room_id`),
    INDEX `idx_perm_uid` (`user_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 16 : Enregistrements Sécurisés & Rétention Programmée
DROP TABLE IF EXISTS `classroom_recordings`;
CREATE TABLE `classroom_recordings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `recording_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` BIGINT DEFAULT 0,
    `duration_seconds` INT DEFAULT 0,
    `recorded_by` VARCHAR(100) NOT NULL,
    `retention_days` INT DEFAULT 30,
    `expires_at` DATETIME NOT NULL,
    `status` VARCHAR(20) DEFAULT 'ready',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rec_meeting` (`meeting_code`),
    INDEX `idx_rec_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 17 : Salles de Sous-Groupes (Breakout Rooms)
DROP TABLE IF EXISTS `classroom_breakout_rooms`;
CREATE TABLE `classroom_breakout_rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `room_index` INT NOT NULL,
    `room_title` VARCHAR(150) NOT NULL,
    `duration_minutes` INT DEFAULT 15,
    `status` VARCHAR(20) DEFAULT 'active',
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `closed_at` DATETIME DEFAULT NULL,
    INDEX `idx_breakout_meeting` (`meeting_code`),
    INDEX `idx_breakout_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 18 : Assignation des Participants aux Sous-Groupes
DROP TABLE IF EXISTS `classroom_breakout_assignments`;
CREATE TABLE `classroom_breakout_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `room_index` INT NOT NULL,
    `peer_id` VARCHAR(64) NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_assign_meeting` (`meeting_code`),
    INDEX `idx_assign_peer` (`peer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 19 : Transcriptions et Sous-Titres en Direct
DROP TABLE IF EXISTS `classroom_transcripts`;
CREATE TABLE `classroom_transcripts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `peer_id` VARCHAR(64) NOT NULL,
    `speaker_name` VARCHAR(100) NOT NULL,
    `speaker_role` VARCHAR(30) DEFAULT 'participant',
    `content` TEXT NOT NULL,
    `timestamp_sec` INT DEFAULT 0,
    `language` VARCHAR(10) DEFAULT 'fr-FR',
    `is_final` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_trans_meeting` (`meeting_code`),
    INDEX `idx_trans_speaker` (`speaker_name`),
    INDEX `idx_trans_time` (`timestamp_sec`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 20 : Résumés et Comptes-Rendus Intelligents de Réunion
DROP TABLE IF EXISTS `classroom_summaries`;
CREATE TABLE `classroom_summaries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `summary_text` TEXT NOT NULL,
    `key_points` TEXT DEFAULT NULL,
    `action_items` TEXT DEFAULT NULL,
    `speakers_stats` TEXT DEFAULT NULL,
    `generated_by` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_summary_meeting` (`meeting_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 21 : Feuilles de Présence, Émargement et Rapports de Session
DROP TABLE IF EXISTS `classroom_attendance`;
CREATE TABLE `classroom_attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_code` VARCHAR(64) NOT NULL,
    `peer_id` VARCHAR(64) NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `user_role` VARCHAR(30) DEFAULT 'participant',
    `user_email` VARCHAR(150) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `device_info` VARCHAR(150) DEFAULT NULL,
    `joined_at` DATETIME NOT NULL,
    `left_at` DATETIME DEFAULT NULL,
    `duration_seconds` INT DEFAULT 0,
    `attendance_percentage` INT DEFAULT 100,
    `status` VARCHAR(20) DEFAULT 'online',
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_att_meeting` (`meeting_code`),
    INDEX `idx_att_user` (`user_name`),
    INDEX `idx_att_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales : Salle principale par défaut
INSERT INTO `classroom_meetings` (`meeting_code`, `title`, `description`, `host_name`, `is_locked`, `require_waiting_room`, `allow_screen_share`, `allow_chat`, `max_participants`, `status`) VALUES
('OSHDI-ROOM-702', 'Session Interactive : Restauration Spirituelle & Autonomie Sociale', 'Visioconférence pastorale et formation communautaire OSHDI en direct', 'Dr. K. N\'Guessan', 0, 1, 1, 1, 50, 'active');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
