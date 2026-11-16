<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\MongoStatistiquesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/statistiques', name: 'api_admin_stats_')]
class StatistiqueController extends AbstractController
{
    private const STATUTS_VALIDES = [
        'terminée', 'terminee', 'livré', 'livre', 'accepté', 'accepte', 'clôturée', 'cloturee'
    ];

    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly MongoStatistiquesService $mongoService
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
            $menuTitre = $request->query->get('menu') ?: null;
            $dateDebut = $request->query->get('dateDebut') ?: null;
            $dateFin   = $request->query->get('dateFin') ?: null;

            $indicateurs = $this->mongoService->getIndicateursGlobaux($menuTitre, $dateDebut, $dateFin);
            $ventesParMenu = $this->mongoService->getStatistiquesVentes($menuTitre, $dateDebut, $dateFin);
            $ventesComparatives = $this->mongoService->getStatistiquesVentes(null, $dateDebut, $dateFin);

            // Si MongoDB n'a pas encore de données synchronisées, on calcule depuis MySQL avec les mêmes filtres
            if (empty($ventesComparatives) && $indicateurs['nbCommandes'] === 0) {
                $fallback = $this->calculerDepuisMysql($menuTitre, $dateDebut, $dateFin);
                $indicateurs = $fallback['indicateurs'];
                $ventesParMenu = $fallback['ventesParMenu'];
                $ventesComparatives = $fallback['ventesComparatives'];
            }

            return new JsonResponse([
                'indicateurs'        => $indicateurs,
                'ventesParMenu'      => $ventesParMenu,
                'ventesComparatives' => $ventesComparatives,
                'filtresAppliques'   => [
                    'menu'      => $menuTitre,
                    'dateDebut' => $dateDebut,
                    'dateFin'   => $dateFin,
                ],
            ], Response::HTTP_OK, $headers);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error'   => 'Erreur serveur statistiques',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR, $headers);
        }
    }

    private function calculerDepuisMysql(?string $menuTitre, ?string $dateDebut, ?string $dateFin): array
    {
        $caTotal = 0.0;
        $nbCommandes = 0;
        $totalConvives = 0;
        $ventesMap = [];

        foreach ($this->commandeRepository->findAll() as $cmd) {
            if (!$this->estCommandeEligble($cmd, $dateDebut, $dateFin)) {
                continue;
            }

            $titre = $cmd->getMenu()?->getTitre() ?? 'Menu traiteur';
            $prix = (float) ($cmd->getPrixTotal() ?? 0);
            $convives = (int) ($cmd->getNbPersonnes() ?? 1);

            $ventesMap[$titre] ??= [
                '_id'             => $titre,
                'chiffreAffaires' => 0.0,
                'totalCommandes'  => 0,
                'totalConvives'   => 0,
            ];
            $ventesMap[$titre]['chiffreAffaires'] += $prix;
            $ventesMap[$titre]['totalCommandes'] += 1;
            $ventesMap[$titre]['totalConvives'] += $convives;

            if ($menuTitre === null || $titre === $menuTitre) {
                $nbCommandes++;
                $caTotal += $prix;
                $totalConvives += $convives;
            }
        }

        $ventesComparatives = array_values($ventesMap);
        usort($ventesComparatives, fn(array $a, array $b) => $b['totalCommandes'] <=> $a['totalCommandes']);

        $ventesParMenu = $menuTitre !== null
            ? array_values(array_filter($ventesComparatives, fn(array $item) => $item['_id'] === $menuTitre))
            : $ventesComparatives;

        return [
            'indicateurs' => [
                'caTotal'       => round($caTotal, 2),
                'nbCommandes'   => $nbCommandes,
                'totalConvives' => $totalConvives,
            ],
            'ventesParMenu'      => $ventesParMenu,
            'ventesComparatives' => $ventesComparatives,
        ];
    }

    private function estCommandeEligble(Commande $cmd, ?string $dateDebut, ?string $dateFin): bool
    {
        $statut = mb_strtolower(trim($cmd->getStatut() ?? ''));
        if (!in_array($statut, self::STATUTS_VALIDES, true)) {
            return false;
        }

        $dateCmd = ($cmd->getDateCommande() ?? $cmd->getDatePrevueLivraison())?->format('Y-m-d');
        if ($dateCmd === null) {
            return $dateDebut === null && $dateFin === null;
        }

        $apresDebut = $dateDebut === null || $dateCmd >= $dateDebut;
        $avantFin = $dateFin === null || $dateCmd <= $dateFin;

        return $apresDebut && $avantFin;
    }
}
