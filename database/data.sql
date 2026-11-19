SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- data pour table `avis`
INSERT INTO `avis` (`id`, `utilisateur_id`, `commande_id`, `note`, `commentaire`, `statut`, `date_creation`) VALUES
(1, 2, 2, 5, 'comme toujours, au top.', 'valide', '2026-11-18 17:17:17'),
(2, 2, 2, 5, 'Top moumoute qualité', 'en_attente', '2026-11-18 17:30:55');

-- data pour table `commande`
INSERT INTO `commande` (`id`, `utilisateur_id`, `menu_id`, `numero_commande`, `date_commande`, `date_prevue_livraison`, `statut`, `prix_total`, `nb_personnes`) VALUES
(1, 2, 1, 'CMD-6AFDCF13000DF', '2026-11-18 17:15:15', '2026-12-26 12:00:00', 'en préparation', 536.54, 11),
(2, 2, 3, 'CMD-6AFDCF4DBEFB0', '2026-11-18 17:16:13', '2026-11-26 12:00:00', 'terminée', 592.95, 15);

-- data pour table `menu`
INSERT INTO `menu` (`id`, `titre`, `description`, `conditions_menu`, `prix_par_personne`, `nb_personnes_min`, `quantite_stock`, `is_actif`, `image`, `theme`, `regime`) VALUES
(1, 'Menu du Terroir & Saveurs', 'Une ode à la gastronomie locale et aux produits de saison. Ce menu met à l\'honneur le savoir-faire de nos producteurs régionaux à travers des recettes généreuses, mijotées avec passion pour vos repas conviviaux.', 'Commande 48h à l\'avance', 48.00, 10, 12, 1, 'https://images.pexels.com/photos/36543118/pexels-photo-36543118.jpeg', 'Classique', 'Carnivore'),
(2, 'Menu Prestige Océan', 'Une parenthèse gastronomique iodée aux accents raffinés. Ce menu d\'exception célèbre la finesse des produits de la mer à travers des associations délicates, créatives et résolument festives pour vos grandes occasions.', 'Commande 72h à l\'avance', 62.00, 8, 100, 1, 'https://images.pexels.com/photos/30413172/pexels-photo-30413172.jpeg', 'Évènement', 'Poisson'),
(3, 'Buffet Champêtre & Gourmand', 'L\'esprit convivial des grandes tablées en plein air. Un buffet généreux et coloré mêlant pièces rôties savoureuses, salades fraîches du marché et douceurs artisanales, idéal pour vos réceptions décontractées.', 'Commande 48h à l\'avance', 39.00, 15, 20, 1, 'https://images.pexels.com/photos/17294750/pexels-photo-17294750.jpeg', 'Classique', 'Carnivore'),
(4, 'Menu Évasion Botanique', 'Une cuisine 100 % végétale, haute en couleurs et pleine de saveurs. Des légumes croquants du marché, des épices douces et des créations gourmandes sans aucun produit d\'origine animale pour un repas sain et raffiné.', 'Commande 48h à l\'avance', 54.00, 10, 15, 1, 'https://images.pexels.com/photos/6327125/pexels-photo-6327125.jpeg', 'Classique', 'Vegan'),
(5, 'Menu Douceur des Saisons', 'Une partition gourmande et généreuse autour des produits du potager, des fromages affinés et des herbes fraîches. Des recettes créatives et réconfortantes qui subliment les saveurs de saison sans viande ni poisson.', 'Commande 48h à l\'avance minimum', 45.00, 10, 14, 1, 'https://images.pexels.com/photos/20488746/pexels-photo-20488746.jpeg', 'Pâques', 'Végétarien'),
(6, 'Menu Prestige des Fêtes', 'La magie des fêtes de fin d\'année réunie autour d\'une table d\'exception. Des mets nobles, des épices douces et des accords raffinés imaginés par nos chefs pour sublimer votre réveillon en famille ou entre amis.', 'Commande 5 jours a l\'avance minimum', 68.00, 6, 8, 1, 'https://images.pexels.com/photos/6062043/pexels-photo-6062043.jpeg', 'Noël', 'Carnivore');

-- data pour table `menu_plat`
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES
(1, 1),
(1, 10),
(1, 11),
(2, 12),
(2, 13),
(2, 14),
(3, 9),
(3, 15),
(3, 16),
(4, 17),
(4, 18),
(4, 19),
(5, 11),
(5, 20),
(5, 21),
(6, 22),
(6, 23),
(6, 24);

-- data pour table `plat`
INSERT INTO `plat` (`id`, `nom`, `description`, `type`, `allergenes`, `stock`, `seuil_alerte`, `image`) VALUES
(1, 'Velouté maraîcher aux éclats de châtaignes', 'Velouté onctueux de légumes anciens du potager mijotés lentement, rehaussé d\'éclats de châtaignes grillées fondantes et d\'un filet d\'huile de noisette torréfiée pour une entrée douce et réconfortante.', 'Entrée', 'Céleri, sulfites', 100, 5, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80'),
(2, 'Noix de Saint-Jacques snackées', 'Noix de Saint-Jacques fraîches saisies à vif pour une croûte dorée et un cœur fondant et nacré, relevées d\'un filet d\'huile vierge aux agrumes et d\'une touche de fleur de sel.', 'Entrée', 'Mollusques, Lait', 100, 5, 'https://images.pexels.com/photos/33942436/pexels-photo-33942436.jpeg'),
(3, 'Tartare de saumon frais à l\'aneth', 'Dés de saumon frais label rouge taillés au couteau, délicatement assaisonnés d\'aneth frais ciselé, de jus de citron jaune, d\'échalote fine et d\'un filet d\'huile d\'olive douce pour une entrée tout en fraîcheur.', 'Entrée', 'Poisson, sésame', 100, 5, 'https://images.pexels.com/photos/17549581/pexels-photo-17549581.jpeg'),
(4, 'Mitonnée de légumes d\'automne et tofu fumé', 'Légumes de saison (potimarron, carottes anciennes, panais et champignons des bois) mijotés lentement dans un bouillon parfumé aux herbes aromatiques, accompagnés de dés de tofu fumé au bois de hêtre dorés à la poêle.', 'Plat', 'Soja', 100, 5, 'https://images.pexels.com/photos/34227765/pexels-photo-34227765.jpeg'),
(5, 'Magret de canard du Sud-Ouest', 'Magret de canard du Sud-Ouest IGP rôti sur peau pour un croustillant doré et une chair rosée fondante, nappé d\'un jus corsé réduit au miel et thym frais, accompagné d\'un écrasé de pommes de terre à l\'huile d\'olive.', 'Plat', 'Lait', 100, 5, 'https://images.pexels.com/photos/29799014/pexels-photo-29799014.jpeg'),
(6, 'Risotto crémeux aux gambas sauvages', 'Risotto crémeux au riz Carnaroli mijoté dans un fumet de crustacés parfumé au safran et parmesan affiné, garni de gambas sauvages snackées à l\'ail doux et au persil plat.', 'Plat', 'Crustacés, Lait', 100, 5, 'https://images.pexels.com/photos/33094519/pexels-photo-33094519.jpeg'),
(7, 'Tartelette croustillante aux poires et amandes', 'Pâte feuilletée dorée et croustillante garnie d\'une crème d\'amande fondante parfumée à la vanille et de fines lamelles de poires Williams caramélisées au four.', 'Dessert', 'Gluten, Fruits à coque', 100, 5, 'https://images.pexels.com/photos/7669783/pexels-photo-7669783.jpeg'),
(8, 'Croustillant de sarrasin et ganache chocolat noir', 'Tuile croustillante au sarrasin torréfié surmontée d\'une ganache onctueuse au chocolat noir intense 70 %, relevée d\'une pointe de fleur de sel pour un contraste croquant et fondant.', 'Dessert', 'Gluten, Lait', 100, 5, 'https://images.pexels.com/photos/29621599/pexels-photo-29621599.jpeg'),
(9, 'Panna cotta vanille Bourbon et coulis fruits rouges', 'Crème onctueuse infusée à la véritable gousse de vanille Bourbon de Madagascar, nappée d\'un coulis artisanal de framboises, fraises et groseilles légèrement acidulé.', 'Dessert', 'Lait', 100, 5, 'https://images.pexels.com/photos/10134248/pexels-photo-10134248.jpeg'),
(10, 'Magret de canard du Sud-Ouest rôti au jus réduit et frites maison', 'Magret de canard du Sud-Ouest IGP rôti sur peau pour un croustillant doré et une chair rosée tendre, accompagné d\'un jus de cuisson corsé réduit aux échalotes et de véritables frites maison dorées et croustillantes.', 'Plat principal', 'Sulfites', 20, 5, 'https://images.pexels.com/photos/17751633/pexels-photo-17751633.jpeg'),
(11, 'Tartelette citron meringuée artisanale', 'Fond de pâte sablée pur beurre croustillante garni d\'un crémeux onctueux au citron jaune acidulé et couronné d\'une meringue italienne aérienne légèrement dorée au chalumeau.', 'Dessert', 'Gluten, Œufs, Lait', 15, 5, 'https://images.unsplash.com/photo-1519915028121-7d3463d20b13?auto=format&fit=crop&w=800&q=80'),
(12, 'Entremet chocolat noir intense, cœur crémeux vanille et éclats de noisettes torréfiées', 'Biscuit moelleux au cacao pur surmonté d\'une mousse onctueuse au chocolat noir intense, renfermant un cœur crémeux à la vanille Bourbon et parsemé de noisettes torréfiées caramélisées.', 'Dessert', 'Gluten, Œufs, Lait, Fruits à coque', 20, 5, 'https://images.pexels.com/photos/8695564/pexels-photo-8695564.jpeg'),
(13, 'Tartare de bar sauvage aux agrumes et huile d\'olive vierge', 'Dés de bar sauvage fraîchement taillés au couteau, marinés minute aux zestes de citron vert et d\'orange douce, relevés d\'une huile d\'olive vierge extra fruitée, de ciboulette ciselée et d\'une pincée de fleur de sel.', 'Entrée', 'Poisson', 20, 5, 'https://images.pexels.com/photos/17249100/pexels-photo-17249100.jpeg'),
(14, 'Noix de Saint-Jacques snackées, émulsion citronnée et risotto crémeux', 'Noix de Saint-Jacques fraîches saisies à la plancha pour une coloration dorée et un cœur nacré fondant, servies sur un risotto crémeux au riz Carnaroli et parmesan affiné, relevé d\'une émulsion légère et acidulée au citron jaune.', 'Plat principal', 'Mollusques, Lait', 20, 5, 'https://images.pexels.com/photos/13312321/pexels-photo-13312321.jpeg'),
(15, 'Assortiment de salades fraîches du marché et tartines campagnardes', 'Mesclun de jeunes pousses croquantes et crudités de saison assaisonnés d\'une vinaigrette légère à la moutarde à l\'ancienne, accompagnés de tartines de pain au levain grillées garnies de légumes marinés aux herbes de Provence.', 'Entrée', 'Gluten, Moutarde', 20, 5, 'https://images.pexels.com/photos/8360233/pexels-photo-8360233.jpeg'),
(16, 'Travers de porc caramélisé au miel', 'Travers de porc français confit à basse température pour une chair ultra-fondante qui se détache à la fourchette, laqué au four d\'une marinade brillante au miel d\'acacia, épices douces et sauce soja, accompagné de pommes de terre grenailles rôties au romarin.', 'Plat principal', 'Sulfites', 20, 5, 'https://images.pexels.com/photos/8251356/pexels-photo-8251356.jpeg'),
(17, 'Velouté onctueux de potimarron rôti, lait de coco et graines de courge grillées', 'Velouté doux et velouté de potimarron rôti au four, adouci au lait de coco crémeux et relevé d\'une pointe de muscade, parsemé de graines de courge torréfiées croquantes pour un contraste gourmand.', 'Entrée', '', 15, 5, 'https://images.pexels.com/photos/10110910/pexels-photo-10110910.jpeg'),
(18, 'Buddha bowl gourmand au tofu laqué, légumes croquants et avocat', 'Base de quinoa et riz complet parfumée au sésame, garnie de dés de tofu bio laqués à la sauce soja et sirop d\'érable, de légumes croquants de saison, d\'avocat frais en lamelles et d\'une sauce crémeuse au tahini et citron vert.', 'Plat principal', 'Soja, Sésame', 15, 5, 'https://images.pexels.com/photos/16028801/pexels-photo-16028801.jpeg'),
(19, 'Dôme chocolat noir intense et coulis de fruits rouges', 'Mousse légère et aérienne au chocolat noir 70 %, dressée sur un biscuit croustillant aux amandes et accompagnée d\'un coulis acidulé de framboises et groseilles fraîches.', 'Dessert', 'Fruits à coque', 15, 5, 'https://images.pexels.com/photos/1998633/pexels-photo-1998633.jpeg'),
(20, 'Burrata crémeuse des Pouilles, légumes rôtis au thym et pesto maison', 'Cœur de burrata des Pouilles fondant et crémeux dressé sur un lit de légumes méditerranéens rôtis au four au thym frais, relevé d\'un filet de pesto artisanal au basilic et pignons de pin.', 'Entrée', 'Lait, Fruits à coque', 15, 5, 'https://images.pexels.com/photos/3669501/pexels-photo-3669501.jpeg'),
(21, 'Risotto crémeux aux champignons des bois, copeaux de parmesan et noisettes torréfiées', 'Risotto crémeux au riz Carnaroli mijoté dans un bouillon parfumé aux cèpes et girolles poêlés au beurre frais, agrémenté de généreux copeaux de Parmigiano Reggiano affiné et d\'éclats de noisettes torréfiées croquantes.', 'Plat principal', 'Lait, Fruits à coque, Sulfites', 15, 5, 'https://images.pexels.com/photos/6216844/pexels-photo-6216844.jpeg'),
(22, 'Médaillon de foie gras mi-cuit au torchon, chutney de figues et brioche toastée', 'Foie gras de canard entier du Sud-Ouest assaisonné au poivre de Sichuan et cuit doucement au torchon, servi avec un chutney fondant aux figues noires et des tranches de brioche artisanale dorées.', 'Entrée', 'Gluten, Œufs, Lait, Sulfites', 15, 5, 'https://images.pexels.com/photos/4186246/pexels-photo-4186246.jpeg'),
(23, 'Suprême de chapon fermier rôti, sauce aux morilles et écrasé de pommes de terre truffé', 'Suprême de chapon fermier rôti lentement au beurre frais pour une chair tendre et juteuse, nappé d\'une sauce onctueuse aux morilles parfumées et accompagné d\'un écrasé de pommes de terre fondant subtilement relevé à la truffe noire.', 'Plat principal', 'Lait, Sulfites', 15, 5, 'https://images.pexels.com/photos/36183161/pexels-photo-36183161.jpeg'),
(24, 'Bûche glacée chocolat noir intense, cœur praliné croustillant et éclats de noisettes', 'Parfait glacé au chocolat noir grand cru 70 %, renfermant un cœur fondant au praliné maison et une base croustillante feuilletée, sublimé par un glaçage intense et des éclats de noisettes torréfiées.', 'Dessert', 'Gluten, Œufs, Lait, Fruits à coque', 15, 5, 'https://images.pexels.com/photos/29978696/pexels-photo-29978696.jpeg');

-- data pour table `utilisateur`
INSERT INTO `utilisateur` (`id`, `email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `reset_token`, `reset_token_expires_at`) VALUES
(1, 'jose@vite-et-gourmand.fr', '[\"ROLE_ADMIN\"]', '$2y$13$FKJSmH8MzpQ.MVioxo5jR.K6Yw/1lEzZYKIJk0qGxm/fJu7V53/Vy', 'Gourmand', 'José', '0556000001', '12 Place de la Bourse, 33000 Bordeaux', NULL, NULL),
(2, 'client@client.fr', '[\"ROLE_USER\"]', '$2y$13$N8eJPnq/5BSUNYAJZ9nHleOD4HjMAbA95L3sYe1zJfzhKS5Q6vCau', 'Doe', 'John', '0601010101', '12 Place de la Foret, 33000 Bordeaux', NULL, NULL),
(3, 'employe@employe.fr', '[\"ROLE_EMPLOYE\"]', '$2y$13$O81438bbY678bA3bIpqiruNbZPy5bTv9z9ym71/raNYUZrPGPtVqG', 'Cousin', 'Hubert', '0556000001', '12 Place de la Tour, 33000 Bordeaux', NULL, NULL),
(4, 'josie@filledegourmand.fr', '[\"ROLE_INACTIF\"]', '$2y$13$umw5BMuUCAamVjOATpuCtumIbrNsd1QQUUz683hCCqHyysjGOh3wa', 'Gourmande', 'josi', '0636656565', 'Bordeaux', NULL, NULL);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

