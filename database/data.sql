SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

INSERT INTO `utilisateur` (`id`, `email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `reset_token`, `reset_token_expires_at`) VALUES
(1, 'jose@vite-et-gourmand.fr', '["ROLE_ADMIN"]', '$2y$13$FKJSmH8MzpQ.MVioxo5jR.K6Yw/1lEzZYKIJk0qGxm/fJu7V53/Vy', 'Gourmand', 'José', '0556000001', '12 Place de la Bourse, 33000 Bordeaux', NULL, NULL),
(2, 'client@client.fr', '["ROLE_USER"]', '$2y$13$N8eJPnq/5BSUNYAJZ9nHleOD4HjMAbA95L3sYe1zJfzhKS5Q6vCau', 'Doe', 'John', '0601010101', '12 Place de la Foret, 33000 Bordeaux', NULL, NULL),
(3, 'employe@employe.fr', '["ROLE_EMPLOYE"]', '$2y$13$O81438bbY678bA3bIpqiruNbZPy5bTv9z9ym71/raNYUZrPGPtVqG', 'Cousin', 'Hubert', '0556000001', '12 Place de la Tour, 33000 Bordeaux', NULL, NULL);

INSERT INTO `plat` (`id`, `nom`, `description`, `type`, `allergenes`, `stock`, `seuil_alerte`) VALUES
(1, 'Velouté maraîcher aux éclats de châtaignes', 'Velouté onctueux de légumes de saison et châtaignes grillées.', 'Entrée', 'Céleri', 0, 5),
(2, 'Noix de Saint-Jacques snackées', 'Saint-Jacques poêlées au beurre demi-sel et émulsion d\'agrumes.', 'Entrée', 'Mollusques, Lait', 20, 5),
(3, 'Tartare de saumon frais à l\'aneth', 'Saumon label rouge coupé au couteau, citron vert et baies roses.', 'Entrée', 'Poisson', 20, 5),
(4, 'Mitonnée de légumes d\'automne et tofu fumé', 'Légumes anciens mijotés aux herbes fraîches et tofu artisanal.', 'Plat', 'Soja', 14, 5),
(5, 'Magret de canard du Sud-Ouest', 'Magret rôti, mousseline de panais et jus réduit au romarin.', 'Plat', 'Lait', 15, 5),
(6, 'Risotto crémeux aux gambas sauvages', 'Riz Carnaroli crémeux, gambas flambées et parmesan affiné.', 'Plat', 'Crustacés, Lait', 20, 5),
(7, 'Tartelette croustillante aux poires et amandes', 'Pâte sablée maison, poires pochées et crème d\'amande.', 'Dessert', 'Gluten, Fruits à coque', 14, 5),
(8, 'Croustillant de sarrasin et ganache chocolat noir', 'Biscuit croustillant au sarrasin et ganache grand cru 70 %.', 'Dessert', 'Gluten, Lait', 25, 5),
(9, 'Panna cotta vanille Bourbon et coulis fruits rouges', 'Crème infusée à la gousse de vanille et coulis maison.', 'Dessert', 'Lait', 15, 5);

INSERT INTO `menu` (`id`, `titre`, `description`, `conditions_menu`, `prix_par_personne`, `nb_personnes_min`, `quantite_stock`, `is_actif`, `image`, `theme`, `regime`) VALUES
(1, 'Menu du Terroir & Saveurs', 'Velouté maraîcher aux éclats de châtaignes, mitonnée de saison et tartelette croustillante.', 'Commande 48h à l\'avance', 48.00, 10, 1, 1, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80', 'Classique', 'Vegan'),
(2, 'Menu Prestige Océan', 'Noix de Saint-Jacques snackées, émulsion d\'agrumes, risotto crémeux et croustillant de sarrasin.', 'Commande 72h à l\'avance', 62.00, 8, 5, 1, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=800&q=80', 'Événement', 'Végétarien'),
(3, 'Buffet Champêtre & Gourmand', 'Magret de canard du Sud-Ouest, mousseline de panais et jus réduit au romarin.', 'Commande 48h à l\'avance', 39.00, 15, 5, 1, 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80', 'Classique', 'Carnivore'),
(4, 'Escapade Marine & Fraîcheur', 'Tartare de saumon frais à l\'aneth, risotto aux gambas et panna cotta vanille Bourbon.', 'Commande 48h à l\'avance', 54.00, 10, 10, 1, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=800&q=80', 'Événement', 'Poisson');

INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES
(1, 1),
(1, 4),
(1, 7),
(2, 2),
(2, 6),
(2, 8),
(3, 1),
(3, 5),
(3, 9),
(4, 3),
(4, 6),
(4, 9);

INSERT INTO `commande` (`id`, `utilisateur_id`, `menu_id`, `numero_commande`, `date_commande`, `date_prevue_livraison`, `statut`, `prix_total`, `nb_personnes`) VALUES
(1, 2, 1, 'CMD-6AFDCF13000DF', '2026-11-18 17:15:15', '2026-12-26 12:00:00', 'en préparation', 536.54, 11),
(2, 2, 3, 'CMD-6AFDCF4DBEFB0', '2026-11-18 17:16:13', '2026-11-26 12:00:00', 'terminée', 592.95, 15);

INSERT INTO `avis` (`id`, `utilisateur_id`, `commande_id`, `note`, `commentaire`, `statut`, `date_creation`) VALUES
(1, 2, 2, 5, 'comme toujours, au top.', 'valide', '2026-11-18 17:17:17');

COMMIT;
