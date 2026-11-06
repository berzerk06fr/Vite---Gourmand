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

class CommandeController extends AbstractController
{
    #[Route('/api/commandes', name: 'api_commandes_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            /** @var Utilisateur|null $user */
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);

            if (empty($data['menuId']) || empty($data['nbPersonnes']) || empty($data['datePrevueLivraison'])) {
                return $this->json(['error' => 'Données incomplètes (menuId, nbPersonnes, datePrevueLivraison requis).'], Response::HTTP_BAD_REQUEST);
            }

            $menu = $em->getRepository(Menu::class)->find($data['menuId']);
            if (!$menu) {
                return $this->json(['error' => 'Menu introuvable.'], Response::HTTP_NOT_FOUND);
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

            $em->persist($commande);
            $em->flush();

            return $this->json([
                'message'        => 'Commande enregistrée avec succès.',
                'numeroCommande' => $commande->getNumeroCommande(),
                'prixTotal'      => $commande->getPrixTotal(),
                'statut'         => $commande->getStatut()
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            return $this->json([
                'erreur_message' => $e->getMessage(),
                'erreur_fichier' => $e->getFile(),
                'erreur_ligne'   => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}