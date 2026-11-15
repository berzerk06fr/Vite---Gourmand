<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    private const DEFAULT_EMAIL = 'vite-et-gourmand-api@alwaysdata.net';
    private const SENDER_EMAIL = 'vite-et-gourmand-api@alwaysdata.net';

    private function getConfigFilePath(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/contact_config.json';
    }

    private function lireDestinataire(): string
    {
        $file = $this->getConfigFilePath();
        if (!file_exists($file)) {
            return self::DEFAULT_EMAIL;
        }

        $data = json_decode((string) file_get_contents($file), true);
        $email = $data['email'] ?? '';

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : self::DEFAULT_EMAIL;
    }

    private function validerPayloadContact(string $expediteur, string $titre, string $description): ?string
    {
        if (!filter_var($expediteur, FILTER_VALIDATE_EMAIL)) {
            return 'Votre adresse email est invalide.';
        }

        if (mb_strlen($titre) < 4 || mb_strlen($description) < 10) {
            return 'Veuillez remplir correctement l\'objet et votre message.';
        }

        return null;
    }

    #[Route('/destinataire', name: 'api_contact_get_destinataire', methods: ['GET'])]
    public function getDestinataire(): JsonResponse
    {
        return $this->json([
            'email' => $this->lireDestinataire(),
        ]);
    }

    #[Route('/destinataire', name: 'api_contact_update_destinataire', methods: ['PUT'])]
    public function updateDestinataire(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        $nouvelEmail = trim((string) ($data['email'] ?? ''));

        if (!filter_var($nouvelEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide.'], 400);
        }

        file_put_contents(
            $this->getConfigFilePath(),
            json_encode(['email' => $nouvelEmail], JSON_PRETTY_PRINT)
        );

        return $this->json([
            'message' => 'Adresse de réception mise à jour.',
            'email' => $nouvelEmail,
        ]);
    }

    #[Route('', name: 'api_contact_send', methods: ['POST'])]
    public function envoyerMessage(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $expediteur = trim((string) ($data['email'] ?? ''));
        $titre = trim((string) ($data['titre'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        $erreur = $this->validerPayloadContact($expediteur, $titre, $description);
        if ($erreur !== null) {
            return $this->json(['error' => $erreur], 400);
        }

        $destinataire = $this->lireDestinataire();

        try {
            $email = (new Email())
                ->from(self::SENDER_EMAIL)
                ->replyTo($expediteur)
                ->to($destinataire)
                ->subject('[Vite & Gourmand - Contact] ' . $titre)
                ->text(
                    "Nouveau message reçu depuis le formulaire de contact :\n\n" .
                    "Expéditeur : {$expediteur}\n" .
                    "Objet : {$titre}\n\n" .
                    "Message :\n{$description}"
                );

            $mailer->send($email);

            return $this->json([
                'message' => 'Votre message a bien été envoyé à notre équipe.',
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur SMTP : ' . $e->getMessage(),
            ], 500);
        }
    }
}
