<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/menus", name="api_menus_")
 */
class ApiMenuController extends AbstractController
{
    /**
     * @Route("", name="index", methods={"GET"})
     */
    public function index(MenuRepository $menuRepository): JsonResponse
    {
        $menus = $menuRepository->findBy(['isActif' => true]);

        return $this->json($menus, Response::HTTP_OK);
    }
}