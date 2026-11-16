<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HoraireController extends AbstractController
{
    private function getFilePath(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/data/footer.json';
    }

    private function getDefaultData(): array
    {
        return [
            'description' => 'Traiteur gastronomique de saison, créateur de moments conviviaux et de réceptions sur-mesure.',
            'reseaux' => [
                'twitter' => '#',
                'facebook' => '#',
                'instagram' => '#',
            ],
            'contact' => [
                'telephone' => '04 93 XX XX XX',
                'email' => 'contact@vite-et-gourmand.fr',
                'adresse' => 'Atelier & Cuisines : Bordeaux',
                'livraison' => 'Livraison Bordeaux & Gironde',
            ],
            'horaires' => [
                ['jour' => 'Lun. – Jeu.', 'heures' => '08h00 – 19h00', 'highlight' => false],
                ['jour' => 'Ven. – Sam.', 'heures' => '08h00 – 20h00', 'highlight' => false],
                ['jour' => 'Dimanche', 'heures' => '09h00 – 14h00', 'highlight' => true],
            ],
        ];
    }

    /**
     * @Route("/api/footer", name="api_footer_index", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        $filePath = $this->getFilePath();

        if (!file_exists($filePath)) {
            return $this->json($this->getDefaultData());
        }

        $data = json_decode(file_get_contents($filePath), true);

        return $this->json($data ?? $this->getDefaultData());
    }

    /**
     * @Route("/api/footer", name="api_footer_update", methods={"PUT"})
     */
    public function update(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès réservé aux employés et administrateurs.');
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Format invalide'], 400);
        }

        $filePath = $this->getFilePath();
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $filePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $this->json($data);
    }
}
