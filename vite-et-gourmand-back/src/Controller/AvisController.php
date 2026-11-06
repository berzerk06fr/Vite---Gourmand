<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/avis', name: 'api_avis_')]
class AvisController extends AbstractController
{
    #[Route('', name: 'public_list', methods: ['GET'])]
    public function listPublic(AvisRepository $avisRepository): JsonResponse
    {
        $avisValides = $avisRepository->findBy(['statut' => 'valide'], ['dateCreation' => 'DESC']);
        $data = array_map(fn (Avis $a) => $this->formaterAvis($a), $avisValides);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/en-attente', name: 'pending_list', methods: ['GET'])]
    public function listEnAttente(AvisRepository $avisRepository): JsonResponse
    {
        $isStaff = $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');
        if (!$isStaff) {
            return $this->json(['error' => 'Accès réservé aux employés et administrateurs.'], Response::HTTP_FORBIDDEN);
        }

        $avisEnAttente = $avisRepository->findBy(['statut' => 'en_attente'], ['dateCreation' => 'ASC']);
        $data = array_map(fn (Avis $a) => $this->formaterAvis($a), $avisEnAttente);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentification requise pour publier un avis.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $note = (int) ($data['note'] ?? 0);
        $commentaire = trim($data['commentaire'] ?? '');

        if ($note < 1 || $note > 5 || empty($commentaire)) {
            return $this->json(['error' => 'Note (entre 1 et 5) et commentaire obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $avis = new Avis();
        $avis->setNote($note);
        $avis->setCommentaire($commentaire);
        $avis->setUtilisateur($user);
        $avis->setStatut('en_attente');

        $em->persist($avis);
        $em->flush();

        return $this->json([
            'message' => 'Votre avis a été soumis et sera publié après modération.',
            'id'      => $avis->getId(),
            'statut'  => $avis->getStatut(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/moderation', name: 'moderer', methods: ['PATCH'])]
    public function moderer(int $id, Request $request, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
    {
        $isStaff = $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');
        $avis = $avisRepository->find($id);
        $data = json_decode($request->getContent(), true) ?? [];
        $action = trim($data['action'] ?? '');

        $erreur = $this->validerModeration($isStaff, $avis, $action);
        if ($erreur !== null) {
            return $erreur;
        }

        $avis->setStatut($action === 'valider' ? 'valide' : 'refuse');
        $em->flush();

        return $this->json([
            'message' => sprintf('Avis %s avec succès.', $avis->getStatut()),
            'statut'  => $avis->getStatut(),
        ], Response::HTTP_OK);
    }

    private function validerModeration(bool $isStaff, ?Avis $avis, string $action): ?JsonResponse
    {
        $response = null;

        if (!$isStaff) {
            $response = $this->json(['error' => 'Accès réservé aux employés et administrateurs.'], Response::HTTP_FORBIDDEN);
        } elseif (!$avis) {
            $response = $this->json(['error' => 'Avis introuvable.'], Response::HTTP_NOT_FOUND);
        } elseif (!in_array($action, ['valider', 'refuser'], true)) {
            $response = $this->json(['error' => 'Action invalide. Choix : "valider" ou "refuser".'], Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    private function formaterAvis(Avis $a): array
    {
        $user = $a->getUtilisateur();
        return [
            'id'           => $a->getId(),
            'note'         => $a->getNote(),
            'commentaire'  => $a->getCommentaire(),
            'statut'       => $a->getStatut(),
            'dateCreation' => $a->getDateCreation()?->format('Y-m-d H:i:s'),
            'auteur'       => $user ? [
                'id'     => $user->getId(),
                'email'  => $user->getUserIdentifier(),
            ] : null,
        ];
    }
}
