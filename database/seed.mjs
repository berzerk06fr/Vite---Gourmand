import mysql from 'mysql2/promise';

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: '', // Laisse vide si aucun mot de passe configuré sur XAMPP/WAMP
  database: 'Vite_et_Gourmand',
  multipleStatements: true
};

async function runSeed() {
  let connection;

  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connexion établie à Vite_et_Gourmand.');

    // 1. Désactivation temporaire des contrôles d'intégrité relationnelle
    await connection.query('SET FOREIGN_KEY_CHECKS = 0;');
    console.log('Clés étrangères désactivées.');

    // 2. Nettoyage complet des tables
    const tables = [
      'historique_statut',
      'avis',
      'commande',
      'menu_plat',
      'plat_allergene',
      'plat',
      'allergene',
      'menu',
      'utilisateur',
      'contact'
    ];

    for (const table of tables) {
      await connection.query(`TRUNCATE TABLE \`${table}\`;`);
    }
    console.log('Tables purgées.');

    // 3. Utilisateurs : 2 staff + 10 clients fixes
    const dummyHash = '$2y$13$dummyhashedpasswordforsecuritytestingpurposes';
    
    const users = [
      // Administrateur & Employé
      [1, 'admin@viteetgourmand.fr', JSON.stringify(['ROLE_ADMIN']), dummyHash, 'Goncalves', 'Angelo', '0601020304', '12 Rue Sainte-Catherine', '33000', 'Bordeaux', 1],
      [2, 'service@viteetgourmand.fr', JSON.stringify(['ROLE_EMPLOYE']), dummyHash, 'Lemoine', 'Claire', '0612345678', '45 Cours Victor Hugo', '33000', 'Bordeaux', 1],
      
      // 10 Clients
      [3, 'julien.moreau@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Moreau', 'Julien', '0611223344', '8 Rue Porte Dijeaux', '33000', 'Bordeaux', 1],
      [4, 'sophie.bernard@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Bernard', 'Sophie', '0622334455', '14 Avenue Thiers', '33100', 'Bordeaux', 1],
      [5, 'thomas.dupont@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Dupont', 'Thomas', '0633445566', '27 Rue Judaïque', '33000', 'Bordeaux', 1],
      [6, 'emma.rousseau@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Rousseau', 'Emma', '0644556677', '5 Cours de la Libération', '33400', 'Talence', 1],
      [7, 'lucas.leroy@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Leroy', 'Lucas', '0655667788', '19 Avenue de la République', '33200', 'Bordeaux', 1],
      [8, 'camille.roux@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Roux', 'Camille', '0666778899', '32 Boulevard Wilson', '33000', 'Bordeaux', 1],
      [9, 'alexandre.david@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'David', 'Alexandre', '0677889900', '3 Rue de Pessac', '33000', 'Bordeaux', 1],
      [10, 'lea.garcia@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Garcia', 'Léa', '0688990011', '82 Avenue d Arès', '33700', 'Mérignac', 1],
      [11, 'nicolas.faure@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Faure', 'Nicolas', '0699001122', '11 Rue du Palais Gallien', '33000', 'Bordeaux', 1],
      [12, 'manon.mercier@email.fr', JSON.stringify(['ROLE_CLIENT']), dummyHash, 'Mercier', 'Manon', '0610293847', '56 Cours Balguerie Stuttenberg', '33300', 'Bordeaux', 1]
    ];

    await connection.query(
      'INSERT INTO `utilisateur` (`id`, `email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `code_postal`, `ville`, `is_actif`) VALUES ?',
      [users]
    );

    // 4. Allergènes
    const allergenes = [
      [1, 'Gluten'], [2, 'Lactose'], [3, 'Arachides'], [4, 'Fruits à coque'],
      [5, 'Crustacés'], [6, 'Œufs'], [7, 'Poisson']
    ];
    await connection.query('INSERT INTO `allergene` (`id`, `nom`) VALUES ?', [allergenes]);

    // 5. Plats
    const plats = [
      [1, 'Velouté de potimarron', 'Éclats de châtaignes grillées et huile de noisette', 'entree', '/assets/images/plats/veloute.webp'],
      [2, 'Céviche de daurade royale', 'Marinade citron vert, grenade et coriandre fraîche', 'entree', '/assets/images/plats/ceviche.webp'],
      [3, 'Carpaccio de betteraves multicolores', 'Crème de cajou ciboulette et graines toastées', 'entree', '/assets/images/plats/carpaccio.webp'],
      [4, 'Pavé de sandre rôti sur peau', 'Émulsion au vin blanc de Pessac et tombée de poireaux', 'plat', '/assets/images/plats/sandre.webp'],
      [5, 'Suprême de volaille fermière', 'Jus corsé au romarin et mousseline de panais', 'plat', '/assets/images/plats/volaille.webp'],
      [6, 'Risotto crémeux d épeautre', 'Poêlée de morilles et asperges vertes au parmesan', 'plat', '/assets/images/plats/risotto.webp'],
      [7, 'Tartelette sablée citron bio', 'Crémeux agrumes et meringue italienne toastée', 'dessert', '/assets/images/plats/tarte.webp'],
      [8, 'Fondant pur chocolat noir 70%', 'Cœur coulant caramel et éclat de vanille Bourbon', 'dessert', '/assets/images/plats/fondant.webp'],
      [9, 'Carpaccio d ananas rôti', 'Infusion à la badiane et sorbet minute mangue-passion', 'dessert', '/assets/images/plats/ananas.webp']
    ];
    await connection.query('INSERT INTO `plat` (`id`, `titre`, `description`, `type`, `image_url`) VALUES ?', [plats]);

    // 6. Associations Plats / Allergènes
    const platAllergenes = [
      [1, 4], [2, 7], [4, 2], [4, 7], [5, 2], [6, 1], [6, 2],
      [7, 1], [7, 2], [7, 6], [8, 1], [8, 2], [8, 6]
    ];
    await connection.query('INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES ?', [platAllergenes]);

    // 7. Menus (8 colonnes strictes, sans champ description)
    const menus = [
      [1, 'Menu Terroir Aquitain', 38.50, 4, 30, 'carnivore', '/assets/images/menus/terroir.webp', 1],
      [2, 'Menu Symphonie Végétale', 32.00, 2, 20, 'vegetarien', '/assets/images/menus/vegetal.webp', 1],
      [3, 'Menu Escapade Océane', 36.00, 4, 15, 'carnivore', '/assets/images/menus/oceane.webp', 1]
    ];
    await connection.query(
      'INSERT INTO `menu` (`id`, `titre`, `prix_par_personne`, `nb_personnes_min`, `quantite_stock`, `regime`, `image_url`, `is_actif`) VALUES ?',
      [menus]
    );

    // 8. Associations Menus / Plats
    const menuPlats = [
      // Menu 1 : Terroir (Velouté, Volaille, Fondant)
      [1, 1], [1, 5], [1, 8],
      // Menu 2 : Végétal (Betteraves, Risotto, Ananas)
      [2, 3], [2, 6], [2, 9],
      // Menu 3 : Océan (Céviche, Sandre, Tartelette)
      [3, 2], [3, 4], [3, 7]
    ];
    await connection.query('INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES ?', [menuPlats]);

    // 9. Commandes initiales
    const commandes = [
      [1, 'CMD-2026-0001', 3, 1, 6, 38.50, 0.00, 0.00, 231.00, '2026-11-15 20:00:00', '8 Rue Porte Dijeaux', '33000', 'Bordeaux', 0.00, 'terminee'],
      [2, 'CMD-2026-0002', 4, 2, 8, 32.00, 25.60, 0.00, 230.40, '2026-11-18 19:30:00', '14 Avenue Thiers', '33100', 'Bordeaux', 0.00, 'validee'],
      [3, 'CMD-2026-0003', 6, 3, 4, 36.00, 0.00, 7.95, 151.95, '2026-11-20 12:30:00', '5 Cours de la Libération', '33400', 'Talence', 5.00, 'en_attente'],
      [4, 'CMD-2026-0004', 10, 1, 10, 38.50, 38.50, 10.90, 357.40, '2026-11-22 20:30:00', '82 Avenue d Arès', '33700', 'Mérignac', 10.00, 'en_preparation']
    ];
    await connection.query(
      'INSERT INTO `commande` (`id`, `numero_commande`, `utilisateur_id`, `menu_id`, `nb_personnes`, `prix_unitaire_applique`, `remise_appliquee`, `frais_livraison`, `prix_total`, `date_prestation`, `adresse_livraison`, `code_postal_livraison`, `ville_livraison`, `distance_km`, `statut_courant`) VALUES ?',
      [commandes]
    );

    // 10. Historique des statuts pour chaque commande
    const historiques = [
      [1, 'en_attente', null, '2026-11-10 10:00:00'],
      [1, 'validee', null, '2026-11-10 14:00:00'],
      [1, 'en_cours_de_livraison', null, '2026-11-15 19:30:00'],
      [1, 'terminee', null, '2026-11-15 20:45:00'],
      [2, 'en_attente', null, '2026-11-12 09:15:00'],
      [2, 'validee', null, '2026-11-12 11:30:00'],
      [3, 'en_attente', null, '2026-11-14 16:45:00'],
      [4, 'en_attente', null, '2026-11-14 18:00:00'],
      [4, 'validee', null, '2026-11-15 08:30:00'],
      [4, 'en_preparation', null, '2026-11-15 14:00:00']
    ];
    await connection.query(
      'INSERT INTO `historique_statut` (`commande_id`, `statut`, `motif`, `date_changement`) VALUES ?',
      [historiques]
    );

    // 11. Avis clients
    const avis = [
      [3, 1, 5, 'Plats raffinés, cuisson parfaite de la volaille et livraison ponctuelle.', 1],
      [4, 2, 5, 'Le menu végétal a conquis tous nos invités, le risotto était remarquable.', 1]
    ];
    await connection.query(
      'INSERT INTO `avis` (`utilisateur_id`, `menu_id`, `note`, `commentaire`, `is_valide`) VALUES ?',
      [avis]
    );

    // 12. Demande de contact
    const contacts = [
      ['Dufour', 'Antoine', 'a.dufour@pro.fr', 'Demande devis séminaire entreprise', 'Bonjour, nous souhaiterions réserver une prestation pour 45 personnes en décembre.', 0]
    ];
    await connection.query(
      'INSERT INTO `contact` (`nom`, `prenom`, `email`, `sujet`, `message`, `is_traite`) VALUES ?',
      [contacts]
    );

    // 13. Réactivation des contraintes de clés
    await connection.query('SET FOREIGN_KEY_CHECKS = 1;');
    console.log('Seeding terminé avec succès : base de données prête.');

  } catch (error) {
    console.error('Erreur lors du seeding :', error);
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

runSeed();