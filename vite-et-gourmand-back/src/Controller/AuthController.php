<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['nom']) || empty($data['prenom'])) {
            return $this->json(['error' => 'Champs obligatoires manquants.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return $this->json(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        $user = new Utilisateur();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setTelephone($data['telephone'] ?? null);
        $user->setAdresse($data['adresse'] ?? $data['adressePostale'] ?? null);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur créé avec succès.',
            'userId' => $user->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'adresse' => $user->getAdresse(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/me', name: 'api_me_update', methods: ['PUT'])]
    public function updateMe(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): JsonResponse {
        /** @var \App\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $nom = trim((string) ($data['nom'] ?? $user->getNom()));
        $prenom = trim((string) ($data['prenom'] ?? $user->getPrenom()));
        $telephone = trim((string) ($data['telephone'] ?? ''));
        $adresse = trim((string) ($data['adresse'] ?? ''));
        $newPassword = (string) ($data['newPassword'] ?? '');

        $error = null;
        if ($nom === '' || $prenom === '') {
            $error = 'Le nom et le prénom sont obligatoires.';
        } elseif ($newPassword !== '' && !$this->isPasswordStrong($newPassword)) {
            $error = 'Le nouveau mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
        }

        if ($error !== null) {
            return $this->json(['error' => $error], Response::HTTP_BAD_REQUEST);
        }

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setTelephone($telephone !== '' ? $telephone : null);
        $user->setAdresse($adresse !== '' ? $adresse : null);

        $passwordChanged = false;
        if ($newPassword !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $passwordChanged = true;
        }

        $em->flush();

        if ($passwordChanged) {
            $this->notifyPasswordChanged($user, $mailer);
        }

        return $this->json([
            'message' => 'Profil mis à jour avec succès.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'telephone' => $user->getTelephone(),
                'adresse' => $user->getAdresse(),
                'roles' => $user->getRoles(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Veuillez saisir une adresse email valide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        $devResetUrl = null;

        if ($user) {
            $token = bin2hex(random_bytes(16));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
            $em->flush();

            if ($this->getParameter('kernel.environment') === 'dev') {
                $devResetUrl = 'http://localhost:4200/reinitialiser-mot-de-passe?token=' . $token;
            }
        }

        $payload = [
            'message' => 'Si un compte correspond à cet email, un lien de réinitialisation vient d\'être généré (valide 1 heure).'
        ];
        if ($devResetUrl !== null) {
            $payload['devResetUrl'] = $devResetUrl;
        }

        return $this->json($payload, Response::HTTP_OK);
    }

    #[Route('/api/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = trim((string) ($data['token'] ?? ''));
        $newPassword = (string) ($data['password'] ?? '');

        if ($token === '' || !$this->isPasswordStrong($newPassword)) {
            return $this->json([
                'error' => 'Jeton manquant ou mot de passe non conforme (10 caractères min., majuscule, minuscule, chiffre et caractère spécial).'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(Utilisateur::class)->findOneBy(['resetToken' => $token]);
        $expiresAt = $user?->getResetTokenExpiresAt();

        if (!$user || ($expiresAt !== null && $expiresAt->getTimestamp() < time())) {
            return $this->json(['error' => 'Ce lien de réinitialisation est invalide, a déjà été utilisé ou a expiré.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $em->flush();

        $this->notifyPasswordChanged($user, $mailer);

        return $this->json(['message' => 'Votre mot de passe a été réinitialisé avec succès.'], Response::HTTP_OK);
    }

    private function notifyPasswordChanged(Utilisateur $user, MailerInterface $mailer): void
    {
        $recipient = $user->getEmail();
        if (empty($recipient)) {
            return;
        }

        $firstName = htmlspecialchars($user->getPrenom() ?? 'Client');
        $body = <<<HTML
            <h2>Confirmation de modification de mot de passe</h2>
            <p>Bonjour {$firstName},</p>
            <p>Le mot de passe associé à votre compte <strong>Vite &amp; Gourmand</strong> a bien été mis à jour.</p>
            <p>Si vous n'avez pas effectué cette démarche, merci de nous contacter rapidement.</p>
            <p>Cordialement,<br>L'équipe Vite &amp; Gourmand</p>
        HTML;

        try {
            $message = (new Email())
                ->from('vite-et-gourmand-api@alwaysdata.net')
                ->to($recipient)
                ->subject('Vite & Gourmand - Confirmation de changement de mot de passe')
                ->html($body);

            $mailer->send($message);
        } catch (\Throwable $e) {
            error_log('Échec envoi mail confirmation mot de passe : ' . $e->getMessage());
        }
    }

    private function isPasswordStrong(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[\W_]/', $password) === 1;
    }
}

