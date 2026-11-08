<?php

namespace App\Controller;

use App\Service\AdminStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin', name: 'api_admin_')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly AdminStatsService $adminStatsService
    ) {}

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès strictement réservé à l\'administrateur.'], Response::HTTP_FORBIDDEN);
        }

        $stats = $this->adminStatsService->genererStatistiques();

        return $this->json($stats, Response::HTTP_OK);
    }
}
