-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 18 déc. 2025 à 00:22
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `urgence_crf_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `interventions`
--

CREATE TABLE `interventions` (
  `id` int(11) NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `commune` varchar(255) NOT NULL,
  `adresse_pma` varchar(255) NOT NULL,
  `adresse_cai` varchar(255) DEFAULT NULL,
  `demandeur` varchar(255) NOT NULL,
  `type_event` varchar(255) NOT NULL,
  `is_acel` tinyint(1) DEFAULT 0,
  `is_cot` tinyint(1) DEFAULT 0,
  `numero_intervention` varchar(50) DEFAULT NULL,
  `statut` enum('En cours','Cloturé','Test') NOT NULL DEFAULT 'En cours',
  `nb_ur` int(11) NOT NULL DEFAULT 0,
  `nb_ua` int(11) NOT NULL DEFAULT 0,
  `nb_dcd` int(11) NOT NULL DEFAULT 0,
  `nb_impliques` int(11) NOT NULL DEFAULT 0,
  `cadres_astreinte` text DEFAULT NULL,
  `cadre_permanence` varchar(255) DEFAULT NULL,
  `cadre_astreinte` varchar(255) DEFAULT NULL,
  `dtus_permanence` varchar(255) DEFAULT NULL,
  `logisticien_astreinte` varchar(255) DEFAULT NULL,
  `aide_regulateur` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `interventions`
--

INSERT INTO `interventions` (`id`, `date_creation`, `commune`, `adresse_pma`, `adresse_cai`, `demandeur`, `type_event`, `is_acel`, `is_cot`, `numero_intervention`, `statut`, `nb_ur`, `nb_ua`, `nb_dcd`, `nb_impliques`, `cadres_astreinte`, `cadre_permanence`, `cadre_astreinte`, `dtus_permanence`, `logisticien_astreinte`, `aide_regulateur`, `description`) VALUES
(1, '2025-12-11 21:52:46', 'Paris 15ème', 'Place de la République, 75015 Paris', 'Siège CRF, 1 rue Henry Barbusse, 75005 Paris', 'SAMU', 'Incendie', 1, 0, 'INT-2024-001', 'Cloturé', 0, 0, 0, 0, 'Jean Dupont - 06 12 34 56 78', NULL, NULL, NULL, NULL, NULL, 'Incendie dans un immeuble résidentiel. Évacuation en cours.'),
(2, '2025-12-11 21:54:25', 'Suresnes', '8bis rue michelet', '', 'Préfecture', 'Autre', 0, 0, '2145055', 'Cloturé', 0, 0, 0, 0, 'Victor thienot', NULL, NULL, NULL, NULL, NULL, 'Ceci est un test'),
(3, '2025-12-11 22:10:01', 'Puteaux', '130 rue de verdun', '', 'SAMU', 'Accident', 1, 1, '25458554547', 'Test', 0, 0, 0, 0, '', 'Thomas Testa', 'juju', 'Leati', 'Raphael', 'Victor', 'Accident, BUS seul');

-- --------------------------------------------------------

--
-- Structure de la table `main_courante`
--

CREATE TABLE `main_courante` (
  `id` int(11) NOT NULL,
  `intervention_id` int(11) NOT NULL,
  `horodatage` datetime NOT NULL DEFAULT current_timestamp(),
  `expediteur` varchar(255) NOT NULL,
  `destinataire` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `moyen_com` varchar(50) DEFAULT NULL,
  `operateur` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `main_courante`
--

INSERT INTO `main_courante` (`id`, `intervention_id`, `horodatage`, `expediteur`, `destinataire`, `message`, `moyen_com`, `operateur`) VALUES
(1, 1, '2025-12-11 21:22:46', 'SAMU', 'PC CRF', 'Demande de renfort', NULL, NULL),
(2, 1, '2025-12-11 21:27:46', 'PC CRF', 'Cadre Astreinte', 'Départ sur les lieux', NULL, NULL),
(3, 1, '2025-12-11 21:32:46', 'Équipe Terrain', 'PC CRF', 'Arrivée sur zone', NULL, NULL),
(4, 1, '2025-12-11 21:37:46', 'Équipe Terrain', 'PC CRF', 'Mise à jour situation', NULL, NULL),
(5, 2, '2025-12-11 21:55:42', 'SAMU', 'Cadre Astreinte', 'Test', NULL, NULL),
(6, 3, '2025-12-11 22:10:54', 'Cadre', 'Police', 'Arrivé de la police', NULL, NULL),
(7, 3, '2025-12-11 22:11:24', 'Cadre Astreinte', 'Cadre Astreinte', 'Arrivée sur zone', NULL, NULL),
(8, 3, '2025-12-11 22:54:18', 'Police', 'SAMU', 'Declanchement du commandemant de police', NULL, NULL),
(9, 3, '2025-12-17 23:14:32', 'SAMU', 'Police', 'Contact CNP', NULL, NULL),
(10, 3, '2025-12-17 23:31:39', 'Police', 'SAMU', 'Contact CO IDF', 'Téléphone', 'Cadre Test'),
(11, 3, '2025-12-17 23:39:33', 'Cadre', 'Police', 'Demande de renfort VPSP', 'Téléphone', 'Cadre Test'),
(12, 3, '2025-12-17 23:39:41', 'Police', 'SAMU', 'Demande d\'ouverture CAI', 'Téléphone', 'Cadre Test'),
(13, 3, '2025-12-17 23:40:05', 'Cadre', 'SAMU', 'Message d\'ambiance', 'Mail', 'Cadre Test');

-- --------------------------------------------------------

--
-- Structure de la table `moyens`
--

CREATE TABLE `moyens` (
  `id` int(11) NOT NULL,
  `intervention_id` int(11) NOT NULL,
  `type` enum('VPSP','VL','MINIBUS','ETIR','BENEVOLE','CADRE','VPSP_PCPS','UMH','GROUPE_BSPP','Autre') NOT NULL,
  `status` enum('dispo','engage') NOT NULL DEFAULT 'dispo',
  `nom_indicatif` varchar(255) NOT NULL,
  `nb_pse` int(11) DEFAULT 0,
  `nb_ch` int(11) DEFAULT 0,
  `nb_ci` int(11) DEFAULT 0,
  `nb_cadre_local` int(11) NOT NULL DEFAULT 0,
  `nb_cadre_dept` int(11) NOT NULL DEFAULT 0,
  `nb_logisticien` int(11) NOT NULL DEFAULT 0,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `moyens`
--

INSERT INTO `moyens` (`id`, `intervention_id`, `type`, `status`, `nom_indicatif`, `nb_pse`, `nb_ch`, `nb_ci`, `nb_cadre_local`, `nb_cadre_dept`, `nb_logisticien`, `date_ajout`) VALUES
(1, 1, 'VPSP', 'engage', 'VPSP-01', 3, 1, 1, 0, 0, 0, '2025-12-11 21:52:46'),
(2, 1, 'VL', 'engage', 'VL-05', 3, 1, 0, 0, 0, 0, '2025-12-11 21:52:46'),
(3, 1, 'VPSP', 'dispo', 'BEN-12', 2, 1, 1, 0, 0, 0, '2025-12-11 21:52:46'),
(4, 1, 'VPSP', 'dispo', 'VPSP-03', 2, 1, 1, 0, 0, 0, '2025-12-11 21:52:46'),
(5, 2, 'VPSP', 'engage', 'Rubis', 0, 0, 0, 0, 0, 0, '2025-12-11 21:54:52'),
(6, 3, 'VPSP', 'engage', 'Rubis', 0, 0, 0, 0, 0, 0, '2025-12-11 22:10:13'),
(7, 3, 'VPSP', 'engage', 'Castor', 0, 0, 0, 0, 0, 0, '2025-12-11 22:10:19'),
(8, 3, 'VL', 'engage', 'Puteaux', 0, 0, 0, 0, 0, 0, '2025-12-11 22:10:26'),
(9, 3, 'BENEVOLE', 'engage', 'Augustin', 0, 0, 0, 0, 0, 0, '2025-12-11 23:10:46'),
(10, 3, 'MINIBUS', 'dispo', 'Rubis', 1, 4, 3, 0, 0, 0, '2025-12-17 23:55:50'),
(11, 3, 'ETIR', 'dispo', 'Rubis', 0, 0, 0, 0, 0, 0, '2025-12-18 00:20:11');

-- --------------------------------------------------------

--
-- Structure de la table `presets_messages`
--

CREATE TABLE `presets_messages` (
  `id` int(11) NOT NULL,
  `categorie` enum('expediteur','destinataire','message') NOT NULL,
  `texte` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `presets_messages`
--

INSERT INTO `presets_messages` (`id`, `categorie`, `texte`) VALUES
(1, 'message', 'Dénombrement terrain provisoire'),
(2, 'message', 'Dénombrement terrain définitif'),
(3, 'message', 'Sur place'),
(4, 'message', 'Quitte les lieux'),
(5, 'message', 'Demande de renfort VPSP'),
(6, 'message', 'Demande de renfort cadres'),
(7, 'message', 'Message d\'ambiance'),
(8, 'message', 'Message de renseignement'),
(9, 'message', 'Demande d\'ouverture CAI'),
(10, 'message', 'Demande d\'ouverture CHU'),
(11, 'message', 'Demande de renfort extradépartemental'),
(12, 'message', 'Contact CNP'),
(13, 'message', 'Contact CO IDF');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `interventions`
--
ALTER TABLE `interventions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date_creation` (`date_creation`),
  ADD KEY `idx_commune` (`commune`);

--
-- Index pour la table `main_courante`
--
ALTER TABLE `main_courante`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_intervention_id` (`intervention_id`),
  ADD KEY `idx_horodatage` (`horodatage`);

--
-- Index pour la table `moyens`
--
ALTER TABLE `moyens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_intervention_id` (`intervention_id`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `presets_messages`
--
ALTER TABLE `presets_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categorie` (`categorie`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `interventions`
--
ALTER TABLE `interventions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `main_courante`
--
ALTER TABLE `main_courante`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `moyens`
--
ALTER TABLE `moyens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `presets_messages`
--
ALTER TABLE `presets_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `main_courante`
--
ALTER TABLE `main_courante`
  ADD CONSTRAINT `main_courante_ibfk_1` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `moyens`
--
ALTER TABLE `moyens`
  ADD CONSTRAINT `moyens_ibfk_1` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
