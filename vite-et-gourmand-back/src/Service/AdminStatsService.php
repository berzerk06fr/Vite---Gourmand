<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;

class AdminStatsService
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly MenuRepository $menuRepository
    ) {}

    public function genererStatistiques(): array
    {
        $commandes = $this->commandeRepository->findAll();
        $menus = $this->menuRepository->findAll();

        $caTotal = 0.0;
        $repartitionStatuts = [];
        $commandesParMenu = [];

        /** @var Commande $cmd */
        foreach ($commandes as $cmd) {
            $prix = (float) ($cmd->getPrixTotal() ?? 0.0);
            $caTotal += $prix;

            $statut = $cmd->getStatut() ?? 'en_attente';
            $repartitionStatuts[$statut] = ($repartitionStatuts[$statut] ?? 0) + 1;

            $menu = $cmd->getMenu();
            if ($menu) {
                $menuId = (int) $menu->getId();
                $commandesParMenu[$menuId] = ($commandesParMenu[$menuId] ?? 0) + 1;
            }
        }

        $statsMenus = $this->construireStatsMenus($menus, $commandesParMenu);

        return [
            'chiffreAffairesTotal' => round($caTotal, 2),
            'nombreTotalCommandes' => count($commandes),
            'repartitionStatuts'   => $repartitionStatuts,
            'menus'                => $statsMenus,
            'dateGeneration'       => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    private function construireStatsMenus(array $menus, array $commandesParMenu): array
    {
        $result = [];
        /** @var Menu $menu */
        foreach ($menus as $menu) {
            $menuId = (int) $menu->getId();
            $result[] = [
                'id'              => $menuId,
                'titre'           => $menu->getTitre(),
                'prixParPersonne' => (float) $menu->getPrixParPersonne(),
                'stockRestant'    => $menu->getQuantiteStock(),
                'nombreCommandes' => $commandesParMenu[$menuId] ?? 0,
            ];
        }

        return $result;
    }
}
