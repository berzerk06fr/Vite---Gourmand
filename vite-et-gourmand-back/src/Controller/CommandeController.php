<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use App\Service\MongoStatistiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/commandes', name: 'api_commandes_')]
class CommandeController extends AbstractController
{
    private const STATUT_ANNULEE = 'annulée';
    private const DEFAULT_MENU_TITRE = 'Menu traiteur';

    private const STATUTS_AUTORISES = [
        'en_attente',
        'accepté',
        'en préparation',
        'en cours de livraison',
        'livré',
        'en attente du retour de matériel',
        'terminée',
        self::STATUT_ANNULEE,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommandeRepository $commandeRepository,
        private readonly MenuRepository $menuRepository,
        private readonly MailerInterface $mailer,
        private readonly MongoStatistiqueService $mongoService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $roles = $user->getRoles();
        $isStaff = in_array('ROLE_EMPLOYE', $roles, true) || in_array('ROLE_ADMIN', $roles, true);

        $commandes = $isStaff
            ? $this->commandeRepository->findBy([], ['id' => 'DESC'])
            : $this->commandeRepository->findBy(['utilisateur' => $user], ['id' => 'DESC']);

        $data = array_map(function (Commande $cmd) {
            $menu = $cmd->getMenu();
            $client = $cmd->getUtilisateur();

            $clientEmail = null;
            if ($client) {
                $clientEmail = method_exists($client, 'getEmail') ? $client->getEmail() : $client->getUserIdentifier();
            }

            return [
                'id'             => $cmd->getId(),
                'numeroCommande' => $cmd->getNumeroCommande() ?? ('CMD-' . $cmd->getId()),
                'nbPersonnes'    => $cmd->getNbPersonnes() ?? 1,
                'prixTotal'      => (float) ($cmd->getPrixTotal() ?? 0),
                'statut'         => $cmd->getStatut() ?? 'en_attente',
                'dateCommande'   => $cmd->getDateCommande()?->format('d/m/Y H:i'),
                'client'         => $client ? [
                    'id'    => $client->getId(),
                    'email' => $clientEmail,
                ] : null,
                'menu'           => $menu ? [
                    'id'    => $menu->getId(),
                    'titre' => $menu->getTitre() ?? 'Menu sans titre',
                ] : [
                    'id'    => 0,
                    'titre' => self::DEFAULT_MENU_TITRE,
                ],
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

    #[Route('/{id}/statut', name: 'update_statut', methods: ['PATCH', 'PUT'])]
    public function updateStatut(int $id, Request $request): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        $data = json_decode($request->getContent(), true) ?? [];

        $checkResponse = $this->verifierAutorisationEtCommande($commande, $data);
        if ($checkResponse !== null) {
            return $checkResponse;
        }

        $ancienStatut = $commande->getStatut();
        $nouveauStatut = trim($data['statut']);
        $commande->setStatut($nouveauStatut);
        $this->em->flush();

        if ($ancienStatut !== $nouveauStatut) {
            $this->envoyerNotificationStatutClient($commande, $nouveauStatut, $data);

            // Synchronisation NoSQL (MongoDB Analytics)
            $menu = $commande->getMenu();
            $this->mongoService->synchroniserCommande([
                'id'             => $commande->getId(),
                'numeroCommande' => $commande->getNumeroCommande() ?? ('CMD-' . $commande->getId()),
                'menuTitre'      => $menu ? ($menu->getTitre() ?? self::DEFAULT_MENU_TITRE) : self::DEFAULT_MENU_TITRE,
                'prixTotal'      => (float) ($commande->getPrixTotal() ?? 0),
                'nbPersonnes'    => (int) ($commande->getNbPersonnes() ?? 1),
                'statut'         => $nouveauStatut,
            ]);
        }

        return $this->json([
            'message'        => sprintf('Statut de la commande mis à jour vers "%s".', $nouveauStatut),
            'numeroCommande' => $commande->getNumeroCommande(),
            'statut'         => $commande->getStatut(),
        ], Response::HTTP_OK);
    }

    private function verifierAutorisationEtCommande(?Commande $commande, array $data): ?JsonResponse
    {
        $response = null;
        $user = $this->getUser();
        $isStaff = $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');

        if (!$user || !$isStaff) {
            $response = $this->json(['error' => 'Accès réservé aux employés et administrateurs.'], Response::HTTP_FORBIDDEN);
        } elseif (!$commande) {
            $response = $this->json(['error' => 'Commande introuvable.'], Response::HTTP_NOT_FOUND);
        } else {
            $validationError = $this->validerModificationStatut($data);
            if ($validationError !== null) {
                $response = $this->json(['error' => $validationError], Response::HTTP_BAD_REQUEST);
            }
        }

        return $response;
    }

    private function validerModificationStatut(array $data): ?string
    {
        $error = null;
        $statut = trim($data['statut'] ?? '');

        if (!in_array($statut, self::STATUTS_AUTORISES, true)) {
            $error = sprintf('Statut invalide. Statuts acceptés : %s.', implode(', ', self::STATUTS_AUTORISES));
        } elseif ($statut === self::STATUT_ANNULEE) {
            $motif = trim($data['motifAnnulation'] ?? '');
            $contact = strtolower(trim($data['modeContact'] ?? ''));

            if (empty($motif) || !in_array($contact, ['gsm', 'mail'], true)) {
                $error = 'Pour annuler, le motif et le mode de contact (gsm ou mail) sont obligatoires.';
            }
        }

        return $error;
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
            } else {
                $error = $this->verifierDisponibilitePlats($menu);
            }
        }

        return $error;
    }

    private function verifierDisponibilitePlats(Menu $menu): ?string
    {
        foreach ($menu->getPlats() as $plat) {
            if ($plat->isEnRupture()) {
                return sprintf('Le plat "%s" de ce menu est actuellement en rupture de stock.', $plat->getNom());
            }
        }

        return null;
    }

    private function enregistrerCommande(Utilisateur $user, Menu $menu, array $data): array
    {
        $nbPersonnes = (int) $data['nbPersonnes'];
        $ville = trim($data['ville'] ?? 'Bordeaux');
        $distanceKm = (float) ($data['distanceKm'] ?? 0.0);

        $nbMin = $menu->getNbPersonnesMin() ?? 1;
        $prixUnitaire = (float) $menu->getPrixParPersonne();
        $sousTotalMenu = $prixUnitaire * $nbPersonnes;
        $reduction = ($nbPersonnes >= ($nbMin + 5));
        if ($reduction) {
            $sousTotalMenu *= 0.90;
        }

        $fraisLivraison = (strcasecmp($ville, 'bordeaux') !== 0) ? (5.0 + (0.59 * $distanceKm)) : 0.0;
        $totalFinal = round($sousTotalMenu + $fraisLivraison, 2);

        // Décrémentation du stock du menu
        $menu->setQuantiteStock(max(0, ($menu->getQuantiteStock() ?? 1) - 1));

        // Décrémentation du stock de chaque plat constitutif
        foreach ($menu->getPlats() as $plat) {
            $plat->setStock($plat->getStock() - 1);
        }

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
        } else {
            $commande->setDatePrevueLivraison(new \DateTime('+7 days'));
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

    private function envoyerNotificationStatutClient(Commande $commande, string $statut, array $data): void
    {
        $client = $commande->getUtilisateur();
        if (!$client || empty($client->getEmail())) {
            return;
        }

        $numero = $commande->getNumeroCommande() ?? ('CMD-' . $commande->getId());
        $sujet = sprintf('Vite & Gourmand : Suivi de votre commande %s', $numero);

        $motifHtml = '';
        if ($statut === self::STATUT_ANNULEE && !empty($data['motifAnnulation'])) {
            $motifHtml = sprintf('<p><strong>Motif :</strong> %s</p>', htmlspecialchars($data['motifAnnulation']));
        }

        $corps = sprintf(
            '<h2>Bonjour,</h2>
            <p>Le statut de votre commande <strong>%s</strong> a évolué.</p>
            <p>Nouveau statut : <strong style="color: #c25e38;">%s</strong></p>
            %s
            <p>Retrouvez tous les détails de votre commande dans votre espace personnel :
                <a href="http://localhost:4200/mes-commandes">Mes Commandes</a>.
            </p>
            <br>
            <p>L\'équipe Vite & Gourmand</p>',
            htmlspecialchars($numero),
            htmlspecialchars($statut),
            $motifHtml
        );

        try {
            $email = (new Email())
                ->from('contact@vite-et-gourmand.fr')
                ->to($client->getEmail())
                ->subject($sujet)
                ->html($corps);

            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            error_log('Erreur envoi notification e-mail : ' . $exception->getMessage());
        }
    }
}
