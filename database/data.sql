USE `Vite_et_Gourmand`;

SET FOREIGN_KEY_CHECKS = 0;


/* =============================
   1. Insertion des utilisateurs
   ============================= */


INSERT INTO `utilisateur` (`id`, `email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `code_postal`, `ville`, `is_actif`) VALUES
(1, 'jose@vite-et-gourmand.fr', '["ROLE_ADMIN"]', '$2y$13$eImiTXuWVxfM37uY4JANjQ==exempleHashAdminBcrypt123456', 'Gourmand', 'José', '0556000001', '12 Rue Sainte-Catherine', '33000', 'Bordeaux', 1),
(2, 'employe@employe.fr', '["ROLE_EMPLOYE"]', '$2y$13$eImiTXuWVxfM37uY4JANjQ==exempleHashEmployeBcrypt1234', 'Martin', 'Julie', '0556000002', '12 Rue Sainte-Catherine', '33000', 'Bordeaux', 1),
(3, 'client@client.fr', '["ROLE_USER"]', '$2y$13$eImiTXuWVxfM37uY4JANjQ==exempleHashClientBcrypt12345', 'Dupont', 'Claire', '0612345678', '45 Cours de l Intendance', '33000', 'Bordeaux', 1);


/* ==============================================
   2. Horaires d'ouverture (du lundi au dimanche)
   ============================================== */


INSERT INTO `horaire` (`id`, `jour_semaine`, `heure_ouverture`, `heure_fermeture`, `est_ferme`) VALUES
(1, 'Lundi', '09:00:00', '19:00:00', 0),
(2, 'Mardi', '09:00:00', '19:00:00', 0),
(3, 'Mercredi', '09:00:00', '19:00:00', 0),
(4, 'Jeudi', '09:00:00', '19:00:00', 0),
(5, 'Vendredi', '09:00:00', '20:00:00', 0),
(6, 'Samedi', '09:00:00', '20:00:00', 0),
(7, 'Dimanche', '10:00:00', '16:00:00', 0);


/* ================================
   3. Régimes, Thèmes et Allergènes
   ================================ */


INSERT INTO `regime` (`id`, `libelle`) VALUES
(1, 'Classique'),
(2, 'Végétarien'),
(3, 'Vegan');

INSERT INTO `theme` (`id`, `libelle`) VALUES
(1, 'Classique'),
(2, 'Évènement'),
(3, 'Noël'),
(4, 'Pâques');

INSERT INTO `allergene` (`id`, `nom`) VALUES
(1, 'Gluten'),
(2, 'Crustacés'),
(3, 'Œufs'),
(4, 'Poissons'),
(5, 'Lait'),
(6, 'Fruits à coque'),
(7, 'Sulfites');


/* =======================================
   4. Plats et liaison avec les allergènes
   ======================================= */


INSERT INTO `plat` (`id`, `titre`, `description`, `type`) VALUES
(1, 'Velouté maraîcher aux éclats de châtaignes', 'Crème onctueuse de légumes de saison et châtaignes torréfiées.', 'entree'),
(2, 'Noix de Saint-Jacques snackées', 'Émulsion d agrumes et croustillant de sarrasin.', 'entree'),
(3, 'Magret de canard du Sud-Ouest', 'Mousseline de panais et jus réduit au romarin.', 'premier_plat'),
(4, 'Suprême de volaille fermière', 'Sauce forestière aux morilles et légumes glacés.', 'premier_plat'),
(5, 'Linguines à l encre de seiche', 'Poulpe snacké aux herbes fraîches et émulsion citronnée.', 'premier_plat'),
(6, 'Tartelette croustillante poire-chocolat', 'Pâte sablée maison, ganache noire et poires pochées.', 'dessert'),
(7, 'Vacherin glacé aux fruits rouges', 'Meringue croquante, sorbet framboise et chantilly vanille.', 'dessert');

INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES
(1, 5),
(2, 2),
(4, 5),
(4, 7),
(5, 1),
(5, 4),
(6, 1),
(6, 3),
(6, 5),
(7, 3),
(7, 5);


/* ==============================
   5. Menus et tables de jointure
   ============================== */


INSERT INTO `menu` (`id`, `titre`, `description`, `conditions_menu`, `prix_par_personne`, `nb_personnes_min`, `quantite_stock`, `is_actif`) VALUES
(1, 'Menu du Terroir & Saveurs', 'Velouté maraîcher aux éclats de châtaignes, mitonnée de saison et tartelette croustillante.', 'Commande au minimum 7 jours avant la date de livraison.', 48.00, 10, 12, 1),
(2, 'Menu Prestige Océan', 'Noix de Saint-Jacques snackées, émulsion d agrumes, risotto crémeux et croustillant de sarrasin.', 'Transport réfrigéré assuré. Maintien au frais obligatoire.', 62.00, 8, 5, 1),
(3, 'Buffet Champêtre & Gourmand', 'Magret de canard du Sud-Ouest, mousseline de panais et jus réduit au romarin.', 'Prêt de vaisselle inclus sous réserve de restitution sous 10 jours.', 39.00, 15, 20, 1),
(4, 'Saveurs Marines & Seiche', 'Linguines à l encre de seiche, poulpe snacké aux herbes fraîches et émulsion citronnée.', 'À déguster chaud dès réception.', 42.00, 10, 10, 1),
(5, 'Déclinaison Végétale & Fraîcheur', 'Fines lamelles de légumes croquants, agrumes rôtis et vinaigrette acidulée.', '100% biologique et local.', 34.00, 10, 15, 1),
(6, 'Plateau Fusion Évasion', 'Assortiment raffiné du chef, saumon mariné et condiments aux perles de saveurs.', 'À consommer dans les 24h suivant la livraison.', 55.00, 12, 15, 1);

INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES
(1, 1), (1, 3), (1, 6),
(2, 2), (2, 4), (2, 7),
(3, 1), (3, 3), (3, 7),
(4, 2), (4, 5), (4, 6),
(5, 1), (5, 6),
(6, 2), (6, 5), (6, 7);

INSERT INTO `menu_regime` (`menu_id`, `regime_id`) VALUES
(1, 1), (2, 1), (3, 1), (4, 1), (5, 3), (6, 1);

INSERT INTO `menu_theme` (`menu_id`, `theme_id`) VALUES
(1, 1), (2, 3), (3, 2), (4, 1), (5, 4), (6, 2);


/* =======================================================================================
   6. Commandes de démonstration (illustrant remise 10%, livraison hors Bordeaux et suivi)
   ======================================================================================= */


INSERT INTO `commande` (`id`, `numero_commande`, `utilisateur_id`, `menu_id`, `nb_personnes`, `prix_unitaire_applique`, `remise_appliquee`, `frais_livraison`, `prix_total`, `date_prestation`, `adresse_livraison`, `code_postal_livraison`, `ville_livraison`, `distance_km`, `statut_courant`, `materiel_prete`, `materiel_rendu`) VALUES
(1, 'CMD-2026-0001', 3, 1, 15, 48.00, 72.00, 0.00, 648.00, '2026-10-15 12:30:00', '45 Cours de l Intendance', '33000', 'Bordeaux', 0.00, 'terminee', 1, 1),
(2, 'CMD-2026-0002', 3, 2, 10, 62.00, 0.00, 13.85, 633.85, '2026-12-24 19:30:00', '18 Avenue de la Libération', '33700', 'Mérignac', 15.00, 'en_attente', 0, 0);

INSERT INTO `historique_statut` (`commande_id`, `statut`, `motif`) VALUES
(1, 'en_attente', NULL),
(1, 'validee', NULL),
(1, 'en_preparation', NULL),
(1, 'en_cours_de_livraison', NULL),
(1, 'terminee', NULL),
(2, 'en_attente', NULL);


/* =======================
   7. Avis clients validés
   ======================= */


INSERT INTO `avis` (`utilisateur_id`, `menu_id`, `note`, `commentaire`, `is_valide`) VALUES
(3, 1, 5, 'Prestation impeccable pour notre repas de famille, produits frais et livraison ponctuelle.', 1),
(3, 2, 5, 'Les Saint-Jacques étaient exceptionnelles, nos invités ont été ravis.', 1);

SET FOREIGN_KEY_CHECKS = 1;