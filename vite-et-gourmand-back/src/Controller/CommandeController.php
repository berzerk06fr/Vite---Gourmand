<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/commandes', name: 'api_commandes_')]
class CommandeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommandeRepository $commandeRepository,
        private readonly MenuRepository $menuRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $commandes = $this->commandeRepository->findBy(['utilisateur' => $user], ['id' => 'DESC']);

        $data = array_map(function (Commande $cmd) {
            $menu = $cmd->getMenu();
            return [
                'id'             => $cmd->getId(),
                'numeroCommande' => method_exists($cmd, 'getNumeroCommande') ? $cmd->getNumeroCommande() : 'CMD-' . $cmd->getId(),
                'nbPersonnes'    => method_exists($cmd, 'getNbPersonnes') ? $cmd->getNbPersonnes() : null,
                'prixTotal'      => method_exists($cmd, 'getPrixTotal') ? (float) $cmd->getPrixTotal() : null,
                'statut'         => method_exists($cmd, 'getStatut') ? $cmd->getStatut() : 'en_attente',
                'menu'           => $menu ? [
                    'id'    => $menu->getId(),
                    'titre' => $menu->getTitre(),
                ] : null,
            ];
        }, $commandes);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentification requise pour commander.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $error = $this->validerRequeteEtMenu($data);
        if ($error !== null) {
            return $this->json(['error' => $error], Response::HTTP_BAD_REQUEST);
        }

        $menu = $this->menuRepository->find($data['menuId']);
        $result = $this->enregistrerCommande($user, $menu, $data);

        return $this->json($result, Response::HTTP_CREATED);
    }

    private function validerRequeteEtMenu(array $data): ?string
    {
        $error = null;
        $nbPersonnes = (int) ($data['nbPersonnes'] ?? 0);

        if (empty($data['menuId']) || $nbPersonnes <= 0) {
            $error = 'Champs obligatoires manquants ou invalides (menuId, nbPersonnes).';
        } else {
            $menu = $this->menuRepository->find($data['menuId']);
            $nbMin = $menu?->getNbPersonnesMin() ?? 1;

            if (!$menu || !$menu->isIsActif()) {
                $error = 'Ce menu n\'est pas disponible.';
            } elseif ($nbPersonnes < $nbMin) {
                $error = sprintf('Le nombre minimum de personnes pour ce menu est de %d.', $nbMin);
            } elseif (($menu->getQuantiteStock() ?? 0) <= 0) {
                $error = 'Ce menu est en rupture de stock.';
            }
        }

        return $error;
    }

   private function enregistrerCommande(Utilisateur $user, Menu $menu, array $data): array
    {
        $nbPersonnes = (int) $data['nbPersonnes'];
        $ville = trim($data['ville'] ?? 'Bordeaux');
        $distanceKm = (float) ($data['distanceKm'] ?? 0.0);

        // 1. Calcul du prix avec réduction 10% si nbPersonnes >= nbMin + 5
        $nbMin = $menu->getNbPersonnesMin() ?? 1;
        $prixUnitaire = (float) $menu->getPrixParPersonne();
        $sousTotalMenu = $prixUnitaire * $nbPersonnes;
        $reduction = ($nbPersonnes >= ($nbMin + 5));
        if ($reduction) {
            $sousTotalMenu *= 0.90;
        }

        // 2. Frais kilométriques hors Bordeaux (5 € + 0.59 € / km)
        $fraisLivraison = (strcasecmp($ville, 'bordeaux') !== 0) ? (5.0 + (0.59 * $distanceKm)) : 0.0;
        $totalFinal = round($sousTotalMenu + $fraisLivraison, 2);

        // 3. Décrémentation du stock du menu
        $menu->setQuantiteStock($menu->getQuantiteStock() - 1);

        // 4. Création de la commande
        $commande = new Commande();
        $commande->setMenu($menu);
        $commande->setUtilisateur($user);
        $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
        $commande->setDateCommande(new \DateTimeImmutable());
        $commande->setNbPersonnes($nbPersonnes);
        $commande->setPrixTotal((string) $totalFinal);
        $commande->setStatut('en_attente');

        if (!empty($data['datePrevueLivraison'])) {
            $commande->setDatePrevueLivraison(new \DateTime($data['datePrevueLivraison']));
        }

        $this->em->persist($commande);
        $this->em->flush();

        return [
            'message'             => 'Commande enregistrée avec succès.',
            'numeroCommande'      => $commande->getNumeroCommande(),
            'menu'                => $menu->getTitre(),
            'nbPersonnes'         => $nbPersonnes,
            'prixMenu'            => round($sousTotalMenu, 2),
            'reduction10Pourcent' => $reduction,
            'fraisLivraison'      => round($fraisLivraison, 2),
            'prixTotal'           => $totalFinal,
            'statut'              => $commande->getStatut(),
            'stockRestant'        => $menu->getQuantiteStock(),
        ];
    }
}
