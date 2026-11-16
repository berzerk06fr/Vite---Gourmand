<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/employes', name: 'api_admin_employes_')]
class AdminEmployerController extends AbstractController
{
    private const ERR_ACCES_ADMIN = 'Accès réservé à l\'administrateur.';
    private const ROLE_ADMIN = 'ROLE_ADMIN';
    private const ROLE_EMPLOYE = 'ROLE_EMPLOYE';
    private const EXPEDITEUR_EMAIL = 'vite-et-gourmand-api@alwaysdata.net';

    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return $this->json(['error' => self::ERR_ACCES_ADMIN], Response::HTTP_FORBIDDEN);
        }

        $tous = $this->utilisateurRepository->findBy([], ['id' => 'DESC']);
        $employes = [];

        foreach ($tous as $u) {
            $roles = $u->getRoles();
            if (in_array(self::ROLE_EMPLOYE, $roles, true) || in_array(self::ROLE_ADMIN, $roles, true)) {
                $employes[] = $this->formatUtilisateur($u);
            }
        }

        return $this->json($employes, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return $this->json(['error' => self::ERR_ACCES_ADMIN], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $validationError = $this->validerCreationEmploye($data);

        if ($validationError !== null) {
            return $this->json(['error' => $validationError['message']], $validationError['code']);
        }

        $token = bin2hex(random_bytes(16));
        $employe = $this->enregistrerNouvelEmploye($data, $token);
        $this->envoyerEmailActivationEmploye($employe, $token);

        return $this->json([
            'message' => 'Compte employé créé avec succès. Un e-mail d\'activation lui a été envoyé.',
            'employe' => $this->formatUtilisateur($employe),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return $this->json(['error' => self::ERR_ACCES_ADMIN], Response::HTTP_FORBIDDEN);
        }

        $utilisateur = $this->utilisateurRepository->find($id);
        $erreurSuppression = $this->verifierSuppressionAutorisee($utilisateur);

        if ($erreurSuppression !== null) {
            return $this->json(['error' => $erreurSuppression['message']], $erreurSuppression['code']);
        }

        $this->em->remove($utilisateur);
        $this->em->flush();

        return $this->json(['message' => 'Accès employé supprimé.'], Response::HTTP_OK);
    }

    private function validerCreationEmploye(array $data): ?array
    {
        $erreur = null;
        $email = strtolower(trim($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($nom) || empty($prenom)) {
            $erreur = ['message' => 'Email valide, nom et prénom sont obligatoires.', 'code' => Response::HTTP_BAD_REQUEST];
        } elseif ($password !== '' && strlen($password) < 8) {
            $erreur = ['message' => 'Le mot de passe provisoire doit contenir au moins 8 caractères.', 'code' => Response::HTTP_BAD_REQUEST];
        } elseif ($this->utilisateurRepository->findOneBy(['email' => $email])) {
            $erreur = ['message' => 'Un compte existe déjà avec cette adresse e-mail.', 'code' => Response::HTTP_CONFLICT];
        }

        return $erreur;
    }

    private function enregistrerNouvelEmploye(array $data, string $token): Utilisateur
    {
        $motDePasseInitial = !empty($data['password'])
            ? (string) $data['password']
            : bin2hex(random_bytes(16));

        $employe = new Utilisateur();
        $employe->setEmail(strtolower(trim($data['email'])));
        $employe->setNom(trim($data['nom']));
        $employe->setPrenom(trim($data['prenom']));
        $employe->setTelephone(trim($data['telephone'] ?? ''));
        $employe->setAdresse(trim($data['adresse'] ?? 'Bordeaux'));
        $employe->setRoles([self::ROLE_EMPLOYE]);
        $employe->setPassword($this->passwordHasher->hashPassword($employe, $motDePasseInitial));
        $employe->setResetToken($token);
        $employe->setResetTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->em->persist($employe);
        $this->em->flush();

        return $employe;
    }

    private function envoyerEmailActivationEmploye(Utilisateur $employe, string $token): void
    {
        $destinataire = $employe->getEmail();
        if (empty($destinataire)) {
            return;
        }

        $lienCreationMdp = 'http://localhost:4200/reinitialiser-mot-de-passe?token=' . urlencode($token);
        $prenom = $employe->getPrenom() ?? 'Collaborateur';

        $corps = sprintf(
            '<h2>Bienvenue dans l\'équipe Vite &amp; Gourmand, %s !</h2>
            <p>Votre compte collaborateur vient d\'être créé par l\'administrateur.</p>
            <p><strong>Identifiant de connexion :</strong> %s</p>
            <p>Pour des raisons de sécurité, aucun mot de passe n\'est transmis par e-mail. Cliquez sur le bouton ci-dessous pour définir votre mot de passe personnel (valide pendant 24 heures) :</p>
            <p style="margin: 24px 0;">
                <a href="%s" style="background-color: #c25e38; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                    Créer mon mot de passe
                </a>
            </p>
            <p>À très vite,<br>Julie &amp; José — Vite &amp; Gourmand</p>',
            htmlspecialchars($prenom),
            htmlspecialchars($destinataire),
            htmlspecialchars($lienCreationMdp)
        );

        try {
            $email = (new Email())
                ->from(self::EXPEDITEUR_EMAIL)
                ->to($destinataire)
                ->subject('Vite & Gourmand : Activation de votre compte employé')
                ->html($corps);

            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            error_log('Erreur envoi e-mail activation employé : ' . $exception->getMessage());
        }
    }

    private function verifierSuppressionAutorisee(?Utilisateur $utilisateur): ?array
    {
        if (!$utilisateur) {
            return ['message' => 'Compte introuvable.', 'code' => Response::HTTP_NOT_FOUND];
        }
        if (in_array(self::ROLE_ADMIN, $utilisateur->getRoles(), true)) {
            return ['message' => 'Impossible de supprimer un compte administrateur.', 'code' => Response::HTTP_BAD_REQUEST];
        }

        return null;
    }

    private function formatUtilisateur(Utilisateur $u): array
    {
        return [
            'id'        => $u->getId(),
            'email'     => $u->getEmail(),
            'nom'       => $u->getNom(),
            'prenom'    => $u->getPrenom(),
            'telephone' => $u->getTelephone(),
            'roles'     => $u->getRoles(),
        ];
    }
}
