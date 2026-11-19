SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table structure pour table `avis`
CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `commande_id` int(11) DEFAULT NULL,
  `note` smallint(6) NOT NULL,
  `commentaire` longtext NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_creation` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure pour table `commande`
CREATE TABLE `commande` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `numero_commande` varchar(50) NOT NULL,
  `date_commande` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `date_prevue_livraison` datetime NOT NULL,
  `statut` varchar(50) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `nb_personnes` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure pour table `doctrine_migration_versions`
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Table structure pour table `menu`
CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `conditions_menu` longtext DEFAULT NULL,
  `prix_par_personne` decimal(10,2) NOT NULL,
  `nb_personnes_min` int(11) NOT NULL,
  `quantite_stock` int(11) NOT NULL,
  `is_actif` tinyint(1) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `theme` varchar(50) DEFAULT NULL,
  `regime` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure pour table `menu_plat`
CREATE TABLE `menu_plat` (
  `menu_id` int(11) NOT NULL,
  `plat_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure pour table `plat`
CREATE TABLE `plat` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `allergenes` longtext DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `seuil_alerte` int(11) NOT NULL DEFAULT 5,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure pour table `utilisateur`
CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes pour table `avis`
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_8F91ABF0FB88E14F` (`utilisateur_id`),
  ADD KEY `IDX_8F91ABF082EA2E54` (`commande_id`);

-- Indexes pour table `commande`
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_6EEAA67DFB88E14F` (`utilisateur_id`),
  ADD KEY `IDX_6EEAA67DCCD7E912` (`menu_id`);

-- Indexes pour table `doctrine_migration_versions`
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

-- Indexes pour table `menu`
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

-- Indexes pour table `menu_plat`
ALTER TABLE `menu_plat`
  ADD PRIMARY KEY (`menu_id`,`plat_id`),
  ADD KEY `IDX_E8775249CCD7E912` (`menu_id`),
  ADD KEY `IDX_E8775249D73DB560` (`plat_id`);

-- Indexes pour table `plat`
ALTER TABLE `plat`
  ADD PRIMARY KEY (`id`);

-- Indexes for table `utilisateur`
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_1D1C63B3E7927C74` (`email`);

-- AUTO_INCREMENT for table `avis`
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `commande`
ALTER TABLE `commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `menu`
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `plat`
ALTER TABLE `plat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `utilisateur`
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Constraints for table `avis`
ALTER TABLE `avis`
  ADD CONSTRAINT `FK_8F91ABF082EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`),
  ADD CONSTRAINT `FK_8F91ABF0FB88E14F` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`);

-- Constraints for table `commande`
ALTER TABLE `commande`
  ADD CONSTRAINT `FK_6EEAA67DCCD7E912` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  ADD CONSTRAINT `FK_6EEAA67DFB88E14F` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`);

-- Constraints for table `menu_plat`
ALTER TABLE `menu_plat`
  ADD CONSTRAINT `FK_E8775249CCD7E912` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_E8775249D73DB560` FOREIGN KEY (`plat_id`) REFERENCES `plat` (`id`) ON DELETE CASCADE;
COMMIT;

