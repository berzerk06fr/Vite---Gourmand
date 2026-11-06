<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
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
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Validation regroupée (données + existence du menu)
        $menu = null;
        if (!empty($data['menuId']) && !empty($data['nbPersonnes']) && !empty($data['datePrevueLivraison'])) {
            $menu = $this->em->getRepository(Menu::class)->find($data['menuId']);
        }

        if (!$menu) {
            return $this->json(['error' => 'Données invalides ou menu introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $nbPersonnes = (int) $data['nbPersonnes'];
        $prixTotal = (float) $menu->getPrixParPersonne() * $nbPersonnes;

        $commande = new Commande();
        $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
        $commande->setDateCommande(new \DateTimeImmutable());
        $commande->setDatePrevueLivraison(new \DateTime($data['datePrevueLivraison']));
        $commande->setStatut('en_attente');
        $commande->setNbPersonnes($nbPersonnes);
        $commande->setPrixTotal((string) $prixTotal);
        $commande->setUtilisateur($user);
        $commande->setMenu($menu);

        $this->em->persist($commande);
        $this->em->flush();

        return $this->json([
            'message'        => 'Commande enregistrée avec succès.',
            'id'             => $commande->getId(),
            'numeroCommande' => $commande->getNumeroCommande(),
            'prixTotal'      => $commande->getPrixTotal(),
            'statut'         => $commande->getStatut()
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $commandes = $this->em->getRepository(Commande::class)->findBy(
            ['utilisateur' => $user],
            ['id' => 'DESC']
        );

        $data = [];
        foreach ($commandes as $cmd) {
            $menu = $cmd->getMenu();
            $data[] = [
                'id'                  => $cmd->getId(),
                'numeroCommande'      => $cmd->getNumeroCommande(),
                'dateCommande'        => $cmd->getDateCommande()?->format('Y-m-d H:i:s'),
                'datePrevueLivraison' => $cmd->getDatePrevueLivraison()?->format('Y-m-d H:i:s'),
                'statut'              => $cmd->getStatut(),
                'nbPersonnes'         => $cmd->getNbPersonnes(),
                'prixTotal'           => $cmd->getPrixTotal(),
                'menu'                => $menu ? [
                    'id'    => $menu->getId(),
                    'titre' => $menu->getTitre()
                ] : null
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }
}