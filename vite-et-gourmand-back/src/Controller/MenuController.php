<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
            $data[] = [
                'id'              => $menu->getId(),
                'titre'           => $menu->getTitre(),
                'description'     => $menu->getDescription(),
                'conditionsMenu'  => $menu->getConditionsMenu(),
                'prixParPersonne' => $menu->getPrixParPersonne(),
                'nbPersonnesMin'  => $menu->getNbPersonnesMin(),
                'quantiteStock'   => $menu->getQuantiteStock(),
                'isActif'         => $menu->isIsActif(),
            ];
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

        return $this->json([
            'id'              => $menu->getId(),
            'titre'           => $menu->getTitre(),
            'description'     => $menu->getDescription(),
            'conditionsMenu'  => $menu->getConditionsMenu(),
            'prixParPersonne' => $menu->getPrixParPersonne(),
            'nbPersonnesMin'  => $menu->getNbPersonnesMin(),
            'quantiteStock'   => $menu->getQuantiteStock(),
            'isActif'         => $menu->isIsActif(),
        ], Response::HTTP_OK);
    }
}
