<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use App\Service\CommandeManager;
use App\Service\MongoStatistiquesService;
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
    private const STATUT_EN_ATTENTE = 'en_attente';
    private const DEFAULT_MENU_TITRE = 'Menu traiteur';
    private const ERR_NON_AUTHENTIFIE = 'Non authentifié.';
    private const ERR_COMMANDE_INTROUVABLE = 'Commande introuvable.';

    private const STATUTS_AUTORISES = [
        self::STATUT_EN_ATTENTE,
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
        private readonly CommandeManager $commandeManager,
        private readonly MailerInterface $mailer,
        private readonly MongoStatistiquesService $mongoService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => self::ERR_NON_AUTHENTIFIE], Response::HTTP_UNAUTHORIZED);
        }

        $isStaff = $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');
        $commandes = $isStaff
            ? $this->commandeRepository->findBy([], ['id' => 'DESC'])
            : $this->commandeRepository->findBy(['utilisateur' => $user], ['id' => 'DESC']);

        $data = array_map(fn (Commande $cmd) => $this->formaterCommandePourListe($cmd), $commandes);

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
        $error = $this->commandeManager->validerCreation($data);
        if ($error !== null) {
            return $this->json(['error' => $error], Response::HTTP_BAD_REQUEST);
        }

        $menu = $this->menuRepository->find($data['menuId']);
        $result = $this->commandeManager->creerCommande($user, $menu, $data);

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
            $this->synchroniserStatistiquesMongo($commande, $nouveauStatut);
        }

        return $this->json([
            'message'        => sprintf('Statut de la commande mis à jour vers "%s".', $nouveauStatut),
            'numeroCommande' => $commande->getNumeroCommande(),
            'statut'         => $commande->getStatut(),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/annuler', name: 'client_annuler', methods: ['PATCH', 'PUT'])]
    public function annulerParClient(int $id): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        $erreurAcces = $this->verifierAccesModificationClient($commande);
        if ($erreurAcces !== null) {
            return $erreurAcces;
        }

        $menu = $commande->getMenu();
        $nbPersonnes = $commande->getNbPersonnes() ?? 0;
        if ($menu && $nbPersonnes > 0) {
            $this->commandeManager->ajusterStockMenuEtPlats($menu, -$nbPersonnes);
        }

        $commande->setStatut(self::STATUT_ANNULEE);
        $this->em->flush();

        return $this->json([
            'message' => 'Votre commande a bien été annulée.',
            'statut'  => $commande->getStatut(),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'client_modifier', methods: ['PUT', 'PATCH'])]
    public function modifierParClient(int $id, Request $request): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        $erreurAcces = $this->verifierAccesModificationClient($commande);
        if ($erreurAcces !== null) {
            return $erreurAcces;
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $resultatValidation = $this->commandeManager->appliquerModificationsClient($commande, $data);
        if ($resultatValidation !== null) {
            return $this->json(['error' => $resultatValidation], Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json([
            'message'        => 'Votre commande a bien été mise à jour.',
            'numeroCommande' => $commande->getNumeroCommande(),
            'nbPersonnes'    => $commande->getNbPersonnes(),
            'prixTotal'      => (float) $commande->getPrixTotal(),
            'statut'         => $commande->getStatut(),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/staff', name: 'staff_modifier', methods: ['PUT', 'PATCH'])]
    public function modifierParStaff(int $id, Request $request): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        $erreurAcces = $this->verifierAccesModificationStaff($commande);
        if ($erreurAcces !== null) {
            return $erreurAcces;
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $erreurValidation = $this->commandeManager->appliquerModificationsStaff($commande, $data);
        if ($erreurValidation !== null) {
            return $this->json(['error' => $erreurValidation], Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();
        $this->synchroniserStatistiquesMongo($commande, $commande->getStatut() ?? self::STATUT_EN_ATTENTE);

        return $this->json([
            'message'        => 'Commande mise à jour par l\'équipe.',
            'numeroCommande' => $commande->getNumeroCommande(),
            'nbPersonnes'    => $commande->getNbPersonnes(),
            'prixTotal'      => (float) $commande->getPrixTotal(),
        ], Response::HTTP_OK);
    }

    private function formaterCommandePourListe(Commande $cmd): array
    {
        $menu = $cmd->getMenu();
        $client = $cmd->getUtilisateur();
        $nbPers = $cmd->getNbPersonnes() ?? 1;
        $prixTotal = (float) ($cmd->getPrixTotal() ?? 0);
        $prixUnitaire = $menu ? (float) $menu->getPrixParPersonne() : 0.0;
        $nbMin = $menu ? ($menu->getNbPersonnesMin() ?? 1) : 1;

        $sousTotalMenu = $prixUnitaire * $nbPers;
        if ($nbPers >= ($nbMin + 5)) {
            $sousTotalMenu *= 0.90;
        }
        $sousTotalMenu = round($sousTotalMenu, 2);

        $fraisLivraison = $prixUnitaire > 0 ? max(0.0, round($prixTotal - $sousTotalMenu, 2)) : 0.0;
        if ($prixUnitaire <= 0) {
            $sousTotalMenu = $prixTotal;
        }

        $clientEmail = null;
        if ($client) {
            $clientEmail = method_exists($client, 'getEmail') ? $client->getEmail() : $client->getUserIdentifier();
        }

        return [
            'id'                  => $cmd->getId(),
            'numeroCommande'      => $cmd->getNumeroCommande() ?? ('CMD-' . $cmd->getId()),
            'nbPersonnes'         => $nbPers,
            'prixMenu'            => $sousTotalMenu,
            'fraisLivraison'      => $fraisLivraison,
            'prixTotal'           => $prixTotal,
            'statut'              => $cmd->getStatut() ?? self::STATUT_EN_ATTENTE,
            'adresse'             => $client?->getAdresse() ?? '',
            'ville'               => 'Bordeaux',
            'dateCommande'        => $cmd->getDateCommande()?->format('d/m/Y H:i'),
            'datePrevueLivraison' => $cmd->getDatePrevueLivraison()?->format('Y-m-d\TH:i'),
            'client'              => $client ? ['id' => $client->getId(), 'email' => $clientEmail] : null,
            'menu'                => $menu
                ? ['id' => $menu->getId(), 'titre' => $menu->getTitre() ?? 'Menu sans titre']
                : ['id' => 0, 'titre' => self::DEFAULT_MENU_TITRE],
        ];
    }

    private function verifierAccesModificationClient(?Commande $commande): ?JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        $response = null;

        if (!$user) {
            $response = $this->json(['error' => self::ERR_NON_AUTHENTIFIE], Response::HTTP_UNAUTHORIZED);
        } elseif (!$commande) {
            $response = $this->json(['error' => self::ERR_COMMANDE_INTROUVABLE], Response::HTTP_NOT_FOUND);
        } elseif ($commande->getUtilisateur()?->getId() !== $user->getId()) {
            $response = $this->json(['error' => 'Accès refusé pour cette commande.'], Response::HTTP_FORBIDDEN);
        } elseif ($commande->getStatut() !== self::STATUT_EN_ATTENTE) {
            $response = $this->json([
                'error' => 'Action impossible : cette commande a déjà été acceptée ou traitée.'
            ], Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    private function verifierAccesModificationStaff(?Commande $commande): ?JsonResponse
    {
        $response = null;
        $isStaff = $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');

        if (!$this->getUser() || !$isStaff) {
            $response = $this->json(['error' => 'Accès réservé aux employés et administrateurs.'], Response::HTTP_FORBIDDEN);
        } elseif (!$commande) {
            $response = $this->json(['error' => self::ERR_COMMANDE_INTROUVABLE], Response::HTTP_NOT_FOUND);
        } elseif (in_array($commande->getStatut(), ['livré', 'terminée', self::STATUT_ANNULEE], true)) {
            $response = $this->json([
                'error' => 'Modification impossible : cette commande est déjà livrée, clôturée ou annulée.'
            ], Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    private function synchroniserStatistiquesMongo(Commande $commande, string $nouveauStatut): void
    {
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

    private function verifierAutorisationEtCommande(?Commande $commande, array $data): ?JsonResponse
    {
        $response = null;
        $user = $this->getUser();
        $isStaff = $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');
        $statut = trim($data['statut'] ?? '');

        if (!$user || !$isStaff) {
            $response = $this->json(['error' => 'Accès réservé aux employés et administrateurs.'], Response::HTTP_FORBIDDEN);
        } elseif (!$commande) {
            $response = $this->json(['error' => self::ERR_COMMANDE_INTROUVABLE], Response::HTTP_NOT_FOUND);
        } elseif (!in_array($statut, self::STATUTS_AUTORISES, true)) {
            $response = $this->json([
                'error' => sprintf('Statut invalide. Statuts acceptés : %s.', implode(', ', self::STATUTS_AUTORISES))
            ], Response::HTTP_BAD_REQUEST);
        } elseif ($statut === self::STATUT_ANNULEE) {
            $motif = trim($data['motifAnnulation'] ?? '');
            $contact = strtolower(trim($data['modeContact'] ?? ''));
            if (empty($motif) || !in_array($contact, ['gsm', 'mail'], true)) {
                $response = $this->json([
                    'error' => 'Pour annuler, le motif et le mode de contact (gsm ou mail) sont obligatoires.'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        return $response;
    }

    private function envoyerNotificationStatutClient(Commande $commande, string $statut, array $data): void
    {
        $client = $commande->getUtilisateur();
        if (!$client || empty($client->getEmail())) {
            return;
        }

        $numero = $commande->getNumeroCommande() ?? ('CMD-' . $commande->getId());
        $motifHtml = ($statut === self::STATUT_ANNULEE && !empty($data['motifAnnulation']))
            ? sprintf('<p><strong>Motif :</strong> %s</p>', htmlspecialchars($data['motifAnnulation']))
            : '';

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
                ->subject(sprintf('Vite & Gourmand : Suivi de votre commande %s', $numero))
                ->html($corps);

            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            error_log('Erreur envoi notification e-mail : ' . $exception->getMessage());
        }
    }
}
