CREATE DATABASE IF NOT EXISTS `Vite_et_Gourmand`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;


USE `Vite_et_Gourmand`;

-- Désactivation temporaire des contrôles de clés pour la purge et reinitialisation de la base de données complete

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `historique_statut`;
DROP TABLE IF EXISTS `avis`;
DROP TABLE IF EXISTS `commande`;
DROP TABLE IF EXISTS `menu_plat`;
DROP TABLE IF EXISTS `plat_allergene`;
DROP TABLE IF EXISTS `plat`;
DROP TABLE IF EXISTS `allergene`;
DROP TABLE IF EXISTS `menu`;
DROP TABLE IF EXISTS `utilisateur`;
DROP TABLE IF EXISTS `contact`;
DROP TABLE IF EXISTS `image_menu`;
DROP TABLE IF EXISTS `menu_theme`;
DROP TABLE IF EXISTS `menu_regime`;
DROP TABLE IF EXISTS `theme`;
DROP TABLE IF EXISTS `regime`;
DROP TABLE IF EXISTS `horaire`;


-- Table utilisateur => rôles possibles-> client, employé, administrateur --

CREATE TABLE `utilisateur` (
    `id` int auto_increment primary key,
    `email` varchar(180) not null unique,
    `roles` Json not null,
    `password` varchar(255) not null,
    `nom` varchar(64) not null,
    `prenom` varchar(32) not null,
    `telephone` varchar(20) not null,
    `adresse` varchar(255) default null,
    `code_postal` varchar(10) default null,
    `ville` varchar(64) default null,
    `is_actif` boolean not null default true,
    `created_at` datetime not null default current_timestamp,
    `updated_at` datetime not null default current_timestamp on update current_timestamp,

    INDEX `idx_utilisateur_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table métier --

CREATE TABLE `horaire`(
    `id` int auto_increment primary key,
    `jour_semaine` varchar(20) not null,
    `heure_ouverture` time default null,
    `heure_fermeture` time default null,
    `est_ferme` boolean not null default false
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des allergènes --

CREATE TABLE `allergene`(
    `id` int auto_increment primary key,
    `nom` varchar(100) not null unique
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des plats --

CREATE TABLE `plat`(
    `id` int auto_increment primary key,
    `titre` varchar(150)not null,
    `description` text default null,
    `type` ENUM ('entree', 'premier_plat', 'second_plat','dessert') not null,
    `image_url` varchar(255) default null,
    `created_at` datetime not null default current_timestamp
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de jointure(plat <--> allergene) --

CREATE TABLE `plat_allergene` (
    `plat_id` int not null,
    `allergene_id`int not null,
    primary key (`plat_id`,`allergene_id`),
    constraint `fk_pa_plat` foreign key (`plat_id`) references `plat` (`id`)
    on delete cascade,
    constraint `fk_pa_allergene` foreign key (`allergene_id`) references `allergene` (`id`)
    on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout Thème et Regime --

CREATE TABLE `regime`(
    `id` int auto_increment primary key,
    `libelle` varchar(100) not null unique
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `theme`(
    `id` int auto_increment primary key,
    `libelle` varchar(100) not null unique
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des menus --

CREATE TABLE `menu`(
    `id` int auto_increment primary key,
    `titre` varchar(150) not null,
    `description` text default null,
    `conditions_menu` text default null comment'Conditions et avertissements obligatoires',
    `prix_par_personne` decimal(10,2) not null,
    `nb_personnes_min` int not null default 1,
    `quantite_stock` int not null default 0,
    `image_url` varchar(255) default null,
    `is_actif` boolean not null default true,
    `created_at` datetime not null default current_timestamp,
    `updated_at` datetime default null on update current_timestamp,

    index `idx_menu_actif` (`is_actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de jointure(menu <--> plats) --

CREATE TABLE `menu_plat`(
    `menu_id` int not null,
    `plat_id` int not null,
    primary key(`menu_id`, `plat_id`),
    constraint `fk_mp_menu` foreign key (`menu_id`) references `menu` (`id`)
    on delete cascade,
    constraint `fk_mp_plat` foreign key (`plat_id`) references `plat` (`id`)
    on delete restrict
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_regime`(
    `menu_id` int not null,
    `regime_id` int not null,
    primary key (`menu_id`,`regime_id`),
    constraint `fk_mr_menu` foreign key (`menu_id`) references `menu` (`id`)
    on delete cascade,
    constraint `fk_mr_regime` foreign key (`regime_id`) references `regime` (`id`)
    on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_theme`(
    `menu_id` int not null,
    `theme_id` int not null,
    primary key (`menu_id`,`theme_id`),
    constraint `fk_mt_menu` foreign key (`menu_id`) references `menu` (`id`)
    on delete cascade,
    constraint `fk_mt_theme` foreign key (`theme_id`) references `theme` (`id`)
    on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fonction multi photo --

CREATE TABLE `image_menu` (
    `id` int auto_increment primary key,
    `menu_id` int not null,
    `url_image` varchar(255) not null,
    `titre_alternatif` varchar(150) not null comment 'Texte alternatif pour accessibilité RGAA',
    `is_principale` boolean not null default false,
    constraint `fk_image_menu` foreign key (`menu_id`) references `menu` (`id`)
    on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des commandes --

CREATE TABLE `commande` (
    `id` int auto_increment primary key,
    `numero_commande` varchar(20) not null unique,
    `utilisateur_id` int not null,
    `menu_id` int not null,
    `nb_personnes` int not null,

-- Règle metier transport/livraison --

    `prix_unitaire_applique` decimal(10,2) not null,
    `remise_appliquee` decimal(10,2) not null default 0.00 comment'10% si nb_personnes >= nb_personnes_min +5',
    `frais_livraison` decimal(10,2) not null default 0.00 comment'Bordeaux = 0€, autre = 5€ + 0.59€/km',
    `prix_total` decimal(10,2) not null,

-- Logistique --

    `date_prestation` datetime not null,
    `adresse_livraison` varchar(255) not null,
    `code_postal_livraison` varchar(10) not null,
    `ville_livraison` varchar(100) not null,
    `distance_km` decimal(6,2) not null default 0.00,
    `statut_courant` ENUM(
        'en_attente',
        'validee',
        'en_preparation',
        'en_cours_de_livraison',
        'terminee',
        'annulee'
    ) not null default 'en_attente',

-- prêt de materiel --
    `materiel_prete` boolean not null default false,
    `materiel_rendu`boolean not null default false,
    `date_limite_restitution` date default null comment 'Restitution sous 10 jours ouvres max',

    `created_at` datetime not null default current_timestamp,
    `updated_at` datetime default null on update current_timestamp,

    index `idx_commande_utilisateur` (`utilisateur_id`),
    index `idx_commande_statut` (`statut_courant`),
    index `idx_commande_date_prestation` (`date_prestation`),

    constraint `fk_commande_utilisateur`
        foreign key (`utilisateur_id`) references `utilisateur` (`id`)
        on delete restrict,

    constraint `fk_commande_menu`
        foreign key (`menu_id`) references `menu` (`id`)
        on delete restrict
) Engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;  

-- table cycle de vie d'une commande --

CREATE TABLE `historique_statut`(
    `id` int auto_increment primary key,
    `commande_id` int not null,
    `statut` ENUM(
        'en_attente',
        'validee',
        'en_preparation',
        'en_cours_de_livraison',
        'terminee',
        'annulee'
    ) NOT NULL,
    `motif` varchar(255) default null comment 'Raison obligatoire en cas d annulation',
    `date_changement` datetime not null default current_timestamp,

    index `idx_historique_commande` (`commande_id`),

    constraint `fk_historique_commande`
        foreign key (`commande_id`) references `commande` (`id`)
        on delete cascade
) Engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;  

-- Table avis --

CREATE TABLE `avis`(
    `id` int auto_increment primary key,
    `utilisateur_id` int not null,
    `menu_id` int not null,
    `note` tinyint unsigned not null comment'Evaluation de 1 à 5',
    `commentaire` text default null,
    `is_valide` boolean not null default false comment'Modéré par le personnel avant affichage public',
    `created_at` datetime not null default current_timestamp,

    index `idx_avis_menu` (`menu_id`),
    index `idx_avis_valide` (`is_valide`),

    constraint `fk_avis_utilisateur`
        foreign key (`utilisateur_id`) references `utilisateur` (`id`)
        on delete cascade,
    constraint `fk_avis_menu`
        foreign key (`menu_id`) references `menu` (`id`)
        on delete cascade
) Engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci; 

-- Table contact --

CREATE TABLE `contact`(
    `id` int auto_increment primary key,
    `nom` varchar(64) not null,
    `prenom` varchar(64) not null,
    `email` varchar(180) not null,
    `sujet` varchar(150) not null,
    `message` text not null,
    `is_traite` boolean not null default false,
    `created_at` datetime not null default current_timestamp,

    index `idx_contact_traite` (`is_traite`)
) Engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci; 

-- Réactivation des vérifications des clés étrangères

set foreign_key_checks = 1;

