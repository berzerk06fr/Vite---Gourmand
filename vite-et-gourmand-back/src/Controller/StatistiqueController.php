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

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        if (!$this->getUser() || !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(
                ['error' => 'Accès réservé uniquement à l\'administrateur.'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $menuTitre = $request->query->get('menu') ?: null;
            $dateDebut = $request->query->get('dateDebut') ?: null;
            $dateFin   = $request->query->get('dateFin') ?: null;

            $indicateurs = $this->mongoService->getIndicateursGlobaux($menuTitre, $dateDebut, $dateFin);
            $ventesParMenu = $this->mongoService->getStatistiquesVentes($menuTitre, $dateDebut, $dateFin);
            $ventesComparatives = $this->mongoService->getStatistiquesVentes(null, $dateDebut, $dateFin);
            $source = 'mongodb';

            // Si la collection MongoDB est encore vide, on initialise MongoDB depuis MySQL puis on relit MongoDB
            if (empty($ventesComparatives) && $indicateurs['nbCommandes'] === 0) {
                $nbSynchronisees = $this->initialiserMongoDepuisMysql();
                if ($nbSynchronisees > 0) {
                    $indicateurs = $this->mongoService->getIndicateursGlobaux($menuTitre, $dateDebut, $dateFin);
                    $ventesParMenu = $this->mongoService->getStatistiquesVentes($menuTitre, $dateDebut, $dateFin);
                    $ventesComparatives = $this->mongoService->getStatistiquesVentes(null, $dateDebut, $dateFin);
                } else {
                    $fallback = $this->calculerDepuisMysql($menuTitre, $dateDebut, $dateFin);
                    $indicateurs = $fallback['indicateurs'];
                    $ventesParMenu = $fallback['ventesParMenu'];
                    $ventesComparatives = $fallback['ventesComparatives'];
                    $source = 'mysql_fallback';
                }
            }

            return $this->json([
                'source'             => $source,
                'indicateurs'        => $indicateurs,
                'ventesParMenu'      => $ventesParMenu,
                'ventesComparatives' => $ventesComparatives,
                'filtresAppliques'   => [
                    'menu'      => $menuTitre,
                    'dateDebut' => $dateDebut,
                    'dateFin'   => $dateFin,
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'error'   => 'Erreur serveur statistiques',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function initialiserMongoDepuisMysql(): int
    {
        $compteur = 0;

        foreach ($this->commandeRepository->findAll() as $cmd) {
            $statut = mb_strtolower(trim($cmd->getStatut() ?? ''));
            if (!in_array($statut, self::STATUTS_VALIDES, true)) {
                continue;
            }

            $menu = $cmd->getMenu();
            $dateCmd = $cmd->getDateCommande() ?? $cmd->getDatePrevueLivraison() ?? new \DateTimeImmutable();

            $ok = $this->mongoService->synchroniserCommande([
                'id'             => $cmd->getId(),
                'numeroCommande' => $cmd->getNumeroCommande() ?? ('CMD-' . $cmd->getId()),
                'menuId'         => $menu ? (int) $menu->getId() : 0,
                'menuTitre'      => $menu?->getTitre() ?? 'Menu traiteur',
                'prixTotal'      => (float) ($cmd->getPrixTotal() ?? 0),
                'nbPersonnes'    => (int) ($cmd->getNbPersonnes() ?? 1),
                'statut'         => $statut,
                'dateCommande'   => $dateCmd->format('Y-m-d'),
            ]);

            if ($ok) {
                $compteur++;
            }
        }

        return $compteur;
    }

    private function calculerDepuisMysql(?string $menuTitre, ?string $dateDebut, ?string $dateFin): array
    {
        $caTotal = 0.0;
        $nbCommandes = 0;
        $totalConvives = 0;
        $ventesMap = [];

        foreach ($this->commandeRepository->findAll() as $cmd) {
            if (!$this->estCommandeEligible($cmd, $dateDebut, $dateFin)) {
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
        usort($ventesComparatives, fn (array $a, array $b) => $b['totalCommandes'] <=> $a['totalCommandes']);

        $ventesParMenu = $menuTitre !== null
            ? array_values(array_filter($ventesComparatives, fn (array $item) => $item['_id'] === $menuTitre))
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

    private function estCommandeEligible(Commande $cmd, ?string $dateDebut, ?string $dateFin): bool
    {
        $statut = mb_strtolower(trim($cmd->getStatut() ?? ''));
        $dateCmd = ($cmd->getDateCommande() ?? $cmd->getDatePrevueLivraison())?->format('Y-m-d');

        if (!in_array($statut, self::STATUTS_VALIDES, true)) {
            return false;
        }

        if ($dateCmd === null) {
            return $dateDebut === null && $dateFin === null;
        }

        return ($dateDebut === null || $dateCmd >= $dateDebut)
            && ($dateFin === null || $dateCmd <= $dateFin);
    }
}
