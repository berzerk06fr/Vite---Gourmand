<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $validationError = $this->validerDonneesInscription($data);
        if ($validationError !== null) {
            return $this->json(['error' => $validationError], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->creerUtilisateur($data);

        return $this->json([
            'message' => 'Compte créé avec succès. Un email de bienvenue a été envoyé.',
            'user' => [
                'id'       => $user->getId(),
                'email'    => $user->getEmail(),
                'nom'      => method_exists($user, 'getNom') ? $user->getNom() : null,
                'prenom'   => method_exists($user, 'getPrenom') ? $user->getPrenom() : null,
                'roles'    => $user->getRoles(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function validerDonneesInscription(array $data): ?string
    {
        $error = null;
        $champsRequis = ['email', 'password', 'nom', 'prenom', 'telephone', 'adressePostale', 'ville', 'cp'];

        foreach ($champsRequis as $champ) {
            if (empty($data[$champ])) {
                $error = sprintf('Le champ obligatoire "%s" est manquant.', $champ);
                break;
            }
        }

        if ($error === null) {
            $error = $this->validerFormatEmailEtMotDePasse($data['email'], $data['password']);
        }

        return $error;
    }

    private function validerFormatEmailEtMotDePasse(string $email, string $password): ?string
    {
        $error = null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Le format de l\'adresse email est invalide.';
        } elseif ($this->utilisateurRepository->findOneBy(['email' => $email])) {
            $error = 'Cette adresse email est déjà utilisée.';
        } elseif (!$this->estMotDePasseSecurise($password)) {
            $error = 'Le mot de passe doit comporter au moins 10 caractères, avec une majuscule, une minuscule, un chiffre et un caractère spécial.';
        }

        return $error;
    }

    private function estMotDePasseSecurise(string $password): bool
    {
        // 10 car. min, au moins 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/';
        return (bool) preg_match($regex, $password);
    }

    private function creerUtilisateur(array $data): Utilisateur
    {
        $user = new Utilisateur();
        $user->setEmail(trim($data['email']));
        $user->setRoles(['ROLE_USER']);

        if (method_exists($user, 'setNom')) {
            $user->setNom(trim($data['nom']));
        }
        if (method_exists($user, 'setPrenom')) {
            $user->setPrenom(trim($data['prenom']));
        }
        if (method_exists($user, 'setTelephone')) {
            $user->setTelephone(trim($data['telephone']));
        }
        if (method_exists($user, 'setAdressePostale')) {
            $user->setAdressePostale(trim($data['adressePostale']));
        }
        if (method_exists($user, 'setVille')) {
            $user->setVille(trim($data['ville']));
        }
        if (method_exists($user, 'setCp')) {
            $user->setCp(trim($data['cp']));
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
