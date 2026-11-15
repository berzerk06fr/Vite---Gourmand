<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommandeManager
{
    private const ERR_NB_PERSONNES_MIN = 'Le nombre minimum de personnes pour ce menu est de %d.';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MenuRepository $menuRepository
    ) {}

    public function validerCreation(array $data): ?string
    {
        $error = null;
        $nbPersonnes = (int) ($data['nbPersonnes'] ?? 0);
        $menu = !empty($data['menuId']) ? $this->menuRepository->find($data['menuId']) : null;
        $nbMin = $menu?->getNbPersonnesMin() ?? 1;
        $stockActuel = $menu?->getQuantiteStock() ?? 0;

        if (empty($data['menuId']) || $nbPersonnes <= 0) {
            $error = 'Champs obligatoires manquants ou invalides (menuId, nbPersonnes).';
        } elseif (!$menu || !$menu->isIsActif()) {
            $error = 'Ce menu n\'est pas disponible.';
        } elseif ($nbPersonnes < $nbMin) {
            $error = sprintf(self::ERR_NB_PERSONNES_MIN, $nbMin);
        } elseif ($stockActuel < $nbPersonnes) {
            $error = sprintf('Stock insuffisant pour ce menu (restant : %d).', $stockActuel);
        } else {
            foreach ($menu->getPlats() as $plat) {
                if ($plat->isEnRupture()) {
                    $error = sprintf('Le plat "%s" de ce menu est actuellement en rupture de stock.', $plat->getNom());
                    break;
                }
            }
        }

        return $error;
    }

    public function creerCommande(Utilisateur $user, Menu $menu, array $data): array
    {
        $nbPersonnes = (int) $data['nbPersonnes'];
        $adresse = trim($data['adresse'] ?? '');

        if (!empty($adresse)) {
            $user->setAdresse($adresse);
            $this->em->persist($user);
            $this->em->flush();
        }

        $nbMin = $menu->getNbPersonnesMin() ?? 1;
        $calculs = $this->calculerTotalCommande($menu, $nbPersonnes, $data);

        $this->ajusterStockMenuEtPlats($menu, $nbPersonnes);

        $commande = new Commande();
        $commande->setMenu($menu);
        $commande->setUtilisateur($user);
        $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
        $commande->setDateCommande(new \DateTimeImmutable());
        $commande->setNbPersonnes($nbPersonnes);
        $commande->setPrixTotal((string) $calculs['total']);
        $commande->setStatut('en_attente');
        $commande->setDatePrevueLivraison(
            !empty($data['datePrevueLivraison'])
                ? new \DateTime($data['datePrevueLivraison'])
                : new \DateTime('+7 days')
        );

        $this->em->persist($commande);
        $this->em->flush();

        return [
            'message'             => 'Commande enregistrée avec succès.',
            'numeroCommande'      => $commande->getNumeroCommande(),
            'menu'                => $menu->getTitre(),
            'nbPersonnes'         => $nbPersonnes,
            'prixMenu'            => $calculs['sousTotal'],
            'reduction10Pourcent' => ($nbPersonnes >= ($nbMin + 5)),
            'fraisLivraison'      => $calculs['livraison'],
            'prixTotal'           => $calculs['total'],
            'statut'              => $commande->getStatut(),
            'stockRestant'        => $menu->getQuantiteStock(),
        ];
    }

    public function appliquerModificationsClient(Commande $commande, array $data): ?string
    {
        $error = null;
        $menu = $commande->getMenu();
        $ancienNb = $commande->getNbPersonnes() ?? 1;
        $nouveauNb = isset($data['nbPersonnes']) ? (int) $data['nbPersonnes'] : $ancienNb;
        $nbMin = $menu?->getNbPersonnesMin() ?? 1;
        $diff = $nouveauNb - $ancienNb;

        if (!$menu) {
            $error = 'Menu associé introuvable.';
        } elseif ($nouveauNb < $nbMin) {
            $error = sprintf(self::ERR_NB_PERSONNES_MIN, $nbMin);
        } elseif ($diff > 0 && ($menu->getQuantiteStock() ?? 0) < $diff) {
            $error = sprintf('Stock insuffisant pour ajouter %d personne(s).', $diff);
        } else {
            $this->ajusterStockMenuEtPlats($menu, $diff);
            $this->mettreAJourInfosLivraison($commande, $data);
            $commande->setNbPersonnes($nouveauNb);
            $commande->setPrixTotal((string) $this->calculerTotalCommande($menu, $nouveauNb, $data)['total']);
        }

        return $error;
    }

    public function appliquerModificationsStaff(Commande $commande, array $data): ?string
    {
        $error = null;
        $ancienMenu = $commande->getMenu();
        $nouveauMenu = !empty($data['menuId']) ? $this->menuRepository->find((int) $data['menuId']) : $ancienMenu;
        $ancienNb = $commande->getNbPersonnes() ?? 1;
        $nouveauNb = isset($data['nbPersonnes']) ? (int) $data['nbPersonnes'] : $ancienNb;
        $nbMin = $nouveauMenu?->getNbPersonnesMin() ?? 1;
        $memeMenu = $ancienMenu?->getId() === $nouveauMenu?->getId();
        $besoinStock = $memeMenu ? ($nouveauNb - $ancienNb) : $nouveauNb;

        if (!$nouveauMenu) {
            $error = 'Menu sélectionné introuvable.';
        } elseif ($nouveauNb < $nbMin) {
            $error = sprintf(self::ERR_NB_PERSONNES_MIN, $nbMin);
        } elseif ($besoinStock > 0 && ($nouveauMenu->getQuantiteStock() ?? 0) < $besoinStock) {
            $error = sprintf('Stock insuffisant pour ce menu (disponible : %d).', $nouveauMenu->getQuantiteStock() ?? 0);
        } else {
            $this->appliquerChangementMenuEtStock($commande, $ancienMenu, $nouveauMenu, $ancienNb, $nouveauNb);
            $this->mettreAJourInfosLivraison($commande, $data);
            $commande->setNbPersonnes($nouveauNb);
            $commande->setPrixTotal((string) $this->calculerTotalCommande($nouveauMenu, $nouveauNb, $data)['total']);
        }

        return $error;
    }

    public function ajusterStockMenuEtPlats(Menu $menu, int $deltaPersonnes): void
    {
        if ($deltaPersonnes === 0) {
            return;
        }
        $menu->setQuantiteStock(max(0, ($menu->getQuantiteStock() ?? 0) - $deltaPersonnes));
        foreach ($menu->getPlats() as $plat) {
            $plat->setStock(max(0, $plat->getStock() - $deltaPersonnes));
        }
    }

    private function appliquerChangementMenuEtStock(
        Commande $commande,
        ?Menu $ancienMenu,
        Menu $nouveauMenu,
        int $ancienNb,
        int $nouveauNb
    ): void {
        if ($ancienMenu?->getId() === $nouveauMenu->getId()) {
            $this->ajusterStockMenuEtPlats($nouveauMenu, $nouveauNb - $ancienNb);
            return;
        }
        if ($ancienMenu) {
            $this->ajusterStockMenuEtPlats($ancienMenu, -$ancienNb);
        }
        $this->ajusterStockMenuEtPlats($nouveauMenu, $nouveauNb);
        $commande->setMenu($nouveauMenu);
    }

    private function mettreAJourInfosLivraison(Commande $commande, array $data): void
    {
        $client = $commande->getUtilisateur();
        if ($client && !empty($data['adresse'])) {
            $client->setAdresse(trim($data['adresse']));
        }
        if (!empty($data['datePrevueLivraison'])) {
            $commande->setDatePrevueLivraison(new \DateTime($data['datePrevueLivraison']));
        }
    }

    private function calculerTotalCommande(Menu $menu, int $nbPersonnes, array $data): array
    {
        $nbMin = $menu->getNbPersonnesMin() ?? 1;
        $sousTotal = (float) $menu->getPrixParPersonne() * $nbPersonnes;
        if ($nbPersonnes >= ($nbMin + 5)) {
            $sousTotal *= 0.90;
        }
        $sousTotalMenu = round($sousTotal, 2);

        $ville = trim($data['ville'] ?? 'Bordeaux');
        $distanceKm = (float) ($data['distanceKm'] ?? 0.0);
        $fraisLivraison = (strcasecmp($ville, 'bordeaux') !== 0 || $distanceKm > 0)
            ? round(5.0 + (0.59 * max(0.0, $distanceKm)), 2)
            : 0.0;

        return [
            'sousTotal' => $sousTotalMenu,
            'livraison' => $fraisLivraison,
            'total'     => round($sousTotalMenu + $fraisLivraison, 2),
        ];
    }
}
