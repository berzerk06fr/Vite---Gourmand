<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Service\MongoStatistiqueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/statistiques', name: 'api_admin_stats_')]
class StatistiqueController extends AbstractController
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly MongoStatistiqueService $mongoService
    ) {}

    #[Route('', name: 'index', methods: ['GET', 'OPTIONS'])]
    public function index(Request $request): JsonResponse
    {
        $headers = [
            'Access-Control-Allow-Origin'  => 'http://localhost:4200',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ];

        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT, $headers);
        }

        try {
            // 1. Lecture analytique via MongoDB
            $indicateurs = $this->mongoService->getIndicateursGlobaux();
            $ventesParMenu = $this->mongoService->getStatistiquesVentes();

            // 2. Si MongoDB ne retourne aucun résultat, fallback sur MySQL
            if (empty($ventesParMenu) && $indicateurs['nbCommandes'] === 0) {
                $commandes = $this->commandeRepository->findAll();
                $caTotal = 0.0;
                $nbCommandes = 0;
                $totalConvives = 0;
                $ventesMap = [];

                foreach ($commandes as $cmd) {
                    $statut = mb_strtolower(trim($cmd->getStatut() ?? ''));
                    if (!in_array($statut, ['terminée', 'terminee', 'livré', 'livre', 'accepté', 'accepte'], true)) {
                        continue;
                    }

                    $nbCommandes++;
                    $prix = (float) ($cmd->getPrixTotal() ?? 0);
                    $convives = (int) ($cmd->getNbPersonnes() ?? 1);
                    $menu = $cmd->getMenu();
                    $titre = $menu ? ($menu->getTitre() ?? 'Menu traiteur') : 'Menu traiteur';

                    $caTotal += $prix;
                    $totalConvives += $convives;

                    if (!isset($ventesMap[$titre])) {
                        $ventesMap[$titre] = [
                            '_id'             => $titre,
                            'chiffreAffaires' => 0.0,
                            'totalCommandes'  => 0,
                            'totalConvives'   => 0,
                        ];
                    }
                    $ventesMap[$titre]['chiffreAffaires'] += $prix;
                    $ventesMap[$titre]['totalCommandes'] += 1;
                    $ventesMap[$titre]['totalConvives'] += $convives;
                }

                $indicateurs = [
                    'caTotal'       => round($caTotal, 2),
                    'nbCommandes'   => $nbCommandes,
                    'totalConvives' => $totalConvives,
                ];

                $ventesParMenu = array_values($ventesMap);
                usort($ventesParMenu, fn($a, $b) => $b['chiffreAffaires'] <=> $a['chiffreAffaires']);
            }

            return new JsonResponse([
                'indicateurs'   => $indicateurs,
                'ventesParMenu' => $ventesParMenu,
            ], Response::HTTP_OK, $headers);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error'   => 'Erreur serveur statistiques',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR, $headers);
        }
    }
}
