<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/admin/statistiques", name="api_admin_stats_")
 */
#[Route('/api/admin/statistiques', name: 'api_admin_stats_')]
class StatistiqueController extends AbstractController
{
    /**
     * @Route("", name="index", methods={"GET", "OPTIONS"})
     */
    #[Route('', name: 'index', methods: ['GET', 'OPTIONS'])]
    public function index(Request $request): JsonResponse
    {
        $headers = [
            'Access-Control-Allow-Origin'  => 'http://localhost:4200',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ];

        // Réponse immédiate pour la requête préliminaire CORS OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT, $headers);
        }

        $data = [
            'indicateurs' => [
                'caTotal'       => 3160.00,
                'nbCommandes'   => 4,
                'totalConvives' => 65
            ],
            'ventesParMenu' => [
                [
                    '_id'             => 'Menu Prestige Océan',
                    'chiffreAffaires' => 2090.00,
                    'totalCommandes'  => 2,
                    'totalConvives'   => 35
                ],
                [
                    '_id'             => 'Buffet Champêtre & Gourmand',
                    'chiffreAffaires' => 620.00,
                    'totalCommandes'  => 1,
                    'totalConvives'   => 20
                ],
                [
                    '_id'             => 'Menu du Terroir & Saveurs',
                    'chiffreAffaires' => 450.00,
                    'totalCommandes'  => 1,
                    'totalConvives'   => 10
                ]
            ]
        ];

        return new JsonResponse($data, Response::HTTP_OK, $headers);
    }
}
