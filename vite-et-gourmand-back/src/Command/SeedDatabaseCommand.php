<?php

namespace App\Command;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Peuple les menus, plats, commandes et avis sans toucher aux comptes utilisateurs existants.'
)]
class SeedDatabaseCommand extends Command
{
    private const TYPE_ENTREE = 'Entrée';
    private const TYPE_PLAT = 'Plat principal';
    private const TYPE_DESSERT = 'Dessert';

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userRepo = $this->em->getRepository(Utilisateur::class);
        $utilisateurs = $userRepo->findAll();

        if (empty($utilisateurs)) {
            $io->error('Aucun utilisateur trouvé en base. Crée au moins un compte avant de lancer cette commande.');
            return Command::FAILURE;
        }

        [$client1, $client2] = $this->resoudreClients($userRepo, $utilisateurs);

        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['avis', 'commande', 'menu_plat', 'menu', 'plat'] as $table) {
            $conn->executeStatement("TRUNCATE TABLE `$table`");
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        // 1. PLATS
        $p1 = $this->creerPlat('Velouté maraîcher aux éclats de châtaignes', 'Émulsion onctueuse de saison et brisures de châtaignes torréfiées.', self::TYPE_ENTREE, 'Fruits à coque', 25, 5);
        $p2 = $this->creerPlat('Carpaccio de Saint-Jacques aux agrumes', 'Fines tranches de Saint-Jacques, huile d\'olive vierge et zestes de yuzu.', self::TYPE_ENTREE, 'Mollusques', 15, 5);
        $p3 = $this->creerPlat('Tartare de légumes croquants & avocat', 'Brunoise fraîche de saison, vinaigrette citronnée aux herbes.', self::TYPE_ENTREE, 'Moutarde', 30, 5);

        $p4 = $this->creerPlat('Magret de canard du Sud-Ouest rôti', 'Magret fermier, jus réduit au romarin et mousseline de panais.', self::TYPE_PLAT, 'Lactose', 20, 5);
        $p5 = $this->creerPlat('Dos de cabillaud en croûte d\'herbes', 'Cuisson basse température, risotto crémeux au safran.', self::TYPE_PLAT, 'Poisson, Lactose', 12, 5);
        $p6 = $this->creerPlat('Mitonnée forestière & tofu fumé bio', 'Champignons des bois, légumes racines rôtis et jus corsé végétal.', self::TYPE_PLAT, 'Soja, Céleri', 4, 5);

        $p7 = $this->creerPlat('Tartelette croustillante poire & chocolat noir', 'Pâte sablée dorée, poires fondantes et ganache 70 % cacao.', self::TYPE_DESSERT, 'Gluten, Lactose, Oeufs', 25, 5);
        $p8 = $this->creerPlat('Pavlova aux fruits rouges de saison', 'Meringue craquante, chantilly mascarpone vanillée et coulis frais.', self::TYPE_DESSERT, 'Oeufs, Lactose', 18, 5);
        $p9 = $this->creerPlat('Panna cotta coco & mangue fraîche', 'Douceur végétale au lait de coco et brunoise de mangue.', self::TYPE_DESSERT, '', 22, 5);

        // 2. MENUS
        $m1 = $this->creerMenu([
            'titre' => 'Menu du Terroir & Saveurs',
            'description' => 'Une ode à la gastronomie du Sud-Ouest avec produits locaux et recettes traditionnelles revisitées.',
            'conditions' => 'Commande au minimum 7 jours avant la date de livraison.',
            'theme' => 'Classique',
            'regime' => 'Carnivore',
            'prix' => '48.00',
            'min' => 10,
            'stock' => 12,
            'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80',
        ], [$p1, $p4, $p7]);

        $m2 = $this->creerMenu([
            'titre' => 'Menu Prestige Océan',
            'description' => 'Saveurs iodées raffinées autour de la Saint-Jacques et du cabillaud sauvage.',
            'conditions' => 'Transport réfrigéré assuré. Maintien au frais obligatoire.',
            'theme' => 'Noël',
            'regime' => 'Poisson',
            'prix' => '62.00',
            'min' => 8,
            'stock' => 6,
            'image' => 'https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=800&q=80',
        ], [$p2, $p5, $p8]);

        $m3 = $this->creerMenu([
            'titre' => 'Déclinaison Végétale & Fraîcheur',
            'description' => 'Menu 100 % végétal élaboré à partir de légumes biologiques de producteurs girondins.',
            'conditions' => 'Adapté aux régimes végétariens et vegan.',
            'theme' => 'Pâques',
            'regime' => 'Vegan',
            'prix' => '34.00',
            'min' => 10,
            'stock' => 15,
            'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=800&q=80',
        ], [$p3, $p6, $p9]);

        $m4 = $this->creerMenu([
            'titre' => 'Buffet Champêtre & Gourmand',
            'description' => 'Formule conviviale idéale pour les réceptions familiales et séminaires d\'entreprise.',
            'conditions' => 'Prêt de vaisselle inclus sous réserve de restitution sous 10 jours.',
            'theme' => 'Évènement',
            'regime' => 'Végétarien',
            'prix' => '39.00',
            'min' => 15,
            'stock' => 10,
            'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80',
        ], [$p1, $p7, $p8]);

        // 3. COMMANDES
        $cmd1 = $this->creerCommande($client1, $m1, [
            'numero' => 'CMD-2026-001',
            'nbPers' => 12,
            'total' => '576.00',
            'statut' => 'terminée',
            'dateCmd' => '-10 days',
            'dateLiv' => '-3 days',
        ]);

        $this->creerCommande($client1, $m2, [
            'numero' => 'CMD-2026-002',
            'nbPers' => 8,
            'total' => '496.00',
            'statut' => 'en_attente',
            'dateCmd' => '-1 day',
            'dateLiv' => '+8 days',
        ]);

        $this->creerCommande($client2, $m3, [
            'numero' => 'CMD-2026-003',
            'nbPers' => 15,
            'total' => '469.90',
            'statut' => 'accepté',
            'dateCmd' => '-2 days',
            'dateLiv' => '+6 days',
        ]);

        $cmd4 = $this->creerCommande($client2, $m4, [
            'numero' => 'CMD-2026-004',
            'nbPers' => 20,
            'total' => '702.00',
            'statut' => 'en attente du retour de matériel',
            'dateCmd' => '-5 days',
            'dateLiv' => '-1 day',
        ]);

        // 4. AVIS CLIENTS
        $avis1 = new Avis();
        $avis1->setUtilisateur($client1);
        $avis1->setCommande($cmd1);
        $avis1->setNote(5);
        $avis1->setCommentaire('Prestation irréprochable ! Le magret était fondant et la livraison ponctuelle.');
        $avis1->setStatut('valide');
        $this->em->persist($avis1);

        $avis2 = new Avis();
        $avis2->setUtilisateur($client2);
        $avis2->setCommande($cmd4);
        $avis2->setNote(4);
        $avis2->setCommentaire('Très bon buffet, nos invités se sont régalés. Nous rendons la vaisselle demain.');
        $avis2->setStatut('en_attente');
        $this->em->persist($avis2);

        $this->em->flush();

        $io->success(sprintf(
            'Catalogue, commandes et avis générés avec succès (rattachés à %s et %s).',
            $client1->getEmail(),
            $client2->getEmail()
        ));

        return Command::SUCCESS;
    }

    /**
     * @param Utilisateur[] $utilisateurs
     * @return array{0: Utilisateur, 1: Utilisateur}
     */
    private function resoudreClients(object $userRepo, array $utilisateurs): array
    {
        $client1 = $userRepo->findOneBy(['email' => 'client@client.fr']);
        $client2 = null;

        foreach ($utilisateurs as $u) {
            if (!in_array('ROLE_ADMIN', $u->getRoles(), true) && !in_array('ROLE_EMPLOYE', $u->getRoles(), true)) {
                if (!$client1) {
                    $client1 = $u;
                } elseif ($u->getId() !== $client1->getId() && !$client2) {
                    $client2 = $u;
                }
            }
        }

        $client1 ??= $utilisateurs[0];
        $client2 ??= $client1;

        return [$client1, $client2];
    }

    private function creerPlat(string $nom, string $desc, string $type, string $allergenes, int $stock, int $seuil): Plat
    {
        $plat = new Plat();
        $plat->setNom($nom);
        $plat->setDescription($desc);
        $plat->setType($type);
        $plat->setAllergenes($allergenes);
        $plat->setStock($stock);
        $plat->setSeuilAlerte($seuil);
        $this->em->persist($plat);

        return $plat;
    }

    /**
     * @param array<string, mixed> $infos
     * @param Plat[] $plats
     */
    private function creerMenu(array $infos, array $plats): Menu
    {
        $menu = new Menu();
        $menu->setTitre($infos['titre']);
        $menu->setDescription($infos['description']);
        $menu->setConditionsMenu($infos['conditions']);
        $menu->setTheme($infos['theme']);
        $menu->setRegime($infos['regime']);
        $menu->setPrixParPersonne($infos['prix']);
        $menu->setNbPersonnesMin($infos['min']);
        $menu->setQuantiteStock($infos['stock']);
        $menu->setIsActif(true);
        $menu->setImage($infos['image']);
        foreach ($plats as $plat) {
            $menu->addPlat($plat);
        }
        $this->em->persist($menu);

        return $menu;
    }

    /**
     * @param array<string, mixed> $infos
     */
    private function creerCommande(Utilisateur $user, Menu $menu, array $infos): Commande
    {
        $cmd = new Commande();
        $cmd->setUtilisateur($user);
        $cmd->setMenu($menu);
        $cmd->setNumeroCommande($infos['numero']);
        $cmd->setNbPersonnes($infos['nbPers']);
        $cmd->setPrixTotal($infos['total']);
        $cmd->setStatut($infos['statut']);
        $cmd->setDateCommande(new \DateTimeImmutable($infos['dateCmd']));
        $cmd->setDatePrevueLivraison(new \DateTime($infos['dateLiv']));
        $this->em->persist($cmd);

        return $cmd;
    }
}
