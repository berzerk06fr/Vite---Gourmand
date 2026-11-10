<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Menu;

#[Route('/api/menus', name: 'api_menus_')]
class MenuController extends AbstractController
{
    public function __construct(
        private readonly MenuRepository $menuRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $menus = $this->menuRepository->findAll();

        $data = [];
        foreach ($menus as $menu) {
            $data[] = $this->formatMenu($menu);
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $menu = $this->menuRepository->find($id);

        if (!$menu) {
            return $this->json(['error' => 'Menu introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->formatMenu($menu), Response::HTTP_OK);
    }

    private function formatMenu(Menu $menu): array
    {
        $platsData = [];
        $allergenesSet = [];

        foreach ($menu->getPlats() as $plat) {
            $rawAllergenes = $plat->getAllergenes();
            $allergenesList = [];

            if ($rawAllergenes) {
                $allergenesList = array_values(array_filter(array_map('trim', explode(',', $rawAllergenes))));
            }

            $platsData[] = [
                'id'            => $plat->getId(),
                'nom'           => $plat->getNom(),
                'description'   => $plat->getDescription(),
                'type'          => $plat->getType(),
                'allergenes'    => $allergenesList,
                'stock'         => $plat->getStock(),
                'seuilAlerte'   => $plat->getSeuilAlerte(),
                'isEnRupture'   => $plat->isEnRupture(),
                'isStockFaible' => $plat->isStockFaible(),
            ];

            foreach ($allergenesList as $alg) {
                $allergenesSet[] = $alg;
            }
        }

        return [
            'id'                => $menu->getId(),
            'titre'             => $menu->getTitre(),
            'description'       => $menu->getDescription(),
            'conditions'        => $menu->getConditionsMenu(),
            'conditionsMenu'    => $menu->getConditionsMenu(),
            'prixParPersonne'   => (float) $menu->getPrixParPersonne(),
            'nombrePersonneMin' => $menu->getNbPersonnesMin(),
            'nbPersonnesMin'    => $menu->getNbPersonnesMin(),
            'quantiteStock'     => $menu->getQuantiteStock(),
            'isActif'           => $menu->isIsActif(),
            'theme'             => 'Classique',
            'regime'            => 'Gourmand',
            'images'            => [
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80'
            ],
            'plats'             => $platsData,
            'allergenes'        => array_values(array_unique($allergenesSet)),
        ];
    }
}
