<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\Plat;
use App\Repository\MenuRepository;
use App\Repository\PlatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/menus', name: 'api_menus_')]
class MenuController extends AbstractController
{
    private const ERR_ACCES_STAFF = 'Accès réservé au personnel autorisé.';
    private const ERR_ACCES_ADMIN = 'Action réservée à l\'administrateur.';
    private const ERR_MENU_INTROUVABLE = 'Menu introuvable.';
    private const ERR_PLAT_INTROUVABLE = 'Plat introuvable.';
    private const ROUTE_ID = '/{id}';
    private const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80';

    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly PlatRepository $platRepository,
        private readonly EntityManagerInterface $em
    ) {}

    // =========================================================================
    // 1. GESTION DES PLATS & STOCKS (CRUD)
    // =========================================================================

    #[Route('/plats/all', name: 'plats_list', methods: ['GET'])]
    public function listPlats(): JsonResponse
    {
        $plats = $this->platRepository->findBy([], ['type' => 'ASC', 'nom' => 'ASC']);
        $data = array_map(fn(Plat $p) => $this->formatPlat($p), $plats);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/plats', name: 'plats_create', methods: ['POST'])]
    public function createPlat(Request $request): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->json(['error' => self::ERR_ACCES_STAFF], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty(trim($data['nom'] ?? '')) || empty(trim($data['type'] ?? ''))) {
            return $this->json(['error' => 'Le nom et le type du plat sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $plat = new Plat();
        $this->hydraterPlat($plat, $data);

        $this->em->persist($plat);
        $this->em->flush();

        return $this->json($this->formatPlat($plat), Response::HTTP_CREATED);
    }

    #[Route('/plats/{id}', name: 'plats_update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function updatePlat(int $id, Request $request): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->json(['error' => self::ERR_ACCES_STAFF], Response::HTTP_FORBIDDEN);
        }

        $plat = $this->platRepository->find($id);
        if (!$plat) {
            return $this->json(['error' => self::ERR_PLAT_INTROUVABLE], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $this->hydraterPlat($plat, $data);
        $this->em->flush();

        return $this->json($this->formatPlat($plat), Response::HTTP_OK);
    }

    #[Route('/plats/{id}', name: 'plats_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deletePlat(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => self::ERR_ACCES_ADMIN], Response::HTTP_FORBIDDEN);
        }

        $plat = $this->platRepository->find($id);
        if (!$plat) {
            return $this->json(['error' => self::ERR_PLAT_INTROUVABLE], Response::HTTP_NOT_FOUND);
        }

        foreach ($plat->getMenus() as $menu) {
            $menu->removePlat($plat);
        }

        $this->em->remove($plat);
        $this->em->flush();

        return $this->json(['message' => 'Plat supprimé.'], Response::HTTP_OK);
    }

    // =========================================================================
    // 2. GESTION DES MENUS & UPLOAD PHOTO
    // =========================================================================

    #[Route('/upload-image', name: 'upload_image', methods: ['POST'], priority: 10)]
    public function uploadImage(Request $request): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->json(['error' => self::ERR_ACCES_STAFF], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('image');
        $erreur = $this->validerFichierImage($file);
        if ($erreur !== null) {
            return $this->json(['error' => $erreur], Response::HTTP_BAD_REQUEST);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/menus';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $filename = uniqid('menu_', true) . '.' . $ext;
        $file->move($uploadDir, $filename);

        return $this->json([
            'url' => $request->getSchemeAndHttpHost() . '/uploads/menus/' . $filename
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $menus = $this->menuRepository->findAll();
        $data = array_map(fn(Menu $menu) => $this->formatMenu($menu), $menus);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route(self::ROUTE_ID, name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $menu = $this->menuRepository->find($id);
        $status = $menu ? Response::HTTP_OK : Response::HTTP_NOT_FOUND;
        $payload = $menu ? $this->formatMenu($menu) : ['error' => self::ERR_MENU_INTROUVABLE];

        return $this->json($payload, $status);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->json(['error' => self::ERR_ACCES_STAFF], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty(trim($data['titre'] ?? '')) || !isset($data['prixParPersonne'])) {
            return $this->json(['error' => 'Le titre et le prix par personne sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $menu = new Menu();
        $this->hydraterMenu($menu, $data);

        $this->em->persist($menu);
        $this->em->flush();

        return $this->json($this->formatMenu($menu), Response::HTTP_CREATED);
    }

    #[Route(self::ROUTE_ID, name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->json(['error' => self::ERR_ACCES_STAFF], Response::HTTP_FORBIDDEN);
        }

        $menu = $this->menuRepository->find($id);
        if (!$menu) {
            return $this->json(['error' => self::ERR_MENU_INTROUVABLE], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $this->hydraterMenu($menu, $data);
        $this->em->flush();

        return $this->json($this->formatMenu($menu), Response::HTTP_OK);
    }

    #[Route(self::ROUTE_ID, name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => self::ERR_ACCES_ADMIN], Response::HTTP_FORBIDDEN);
        }

        $menu = $this->menuRepository->find($id);
        if (!$menu) {
            return $this->json(['error' => self::ERR_MENU_INTROUVABLE], Response::HTTP_NOT_FOUND);
        }

        $message = $this->supprimerOuDesactiverMenu($menu);
        return $this->json(['message' => $message], Response::HTTP_OK);
    }

    // =========================================================================
    // MÉTHODES UTILITAIRES
    // =========================================================================

    private function isStaff(): bool
    {
        return $this->isGranted('ROLE_EMPLOYE') || $this->isGranted('ROLE_ADMIN');
    }

    private function validerFichierImage(mixed $file): ?string
    {
        if (!$file) {
            return 'Aucun fichier reçu (vérifiez que l\'image fait moins de 2 Mo).';
        }
        if (!$file->isValid()) {
            return 'Fichier trop volumineux ou invalide : ' . $file->getErrorMessage();
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        return in_array($ext, $allowedExts, true)
            ? null
            : 'Format non supporté (JPG, PNG, WEBP, AVIF uniquement).';
    }

    private function supprimerOuDesactiverMenu(Menu $menu): string
    {
        if (!$menu->getCommandes()->isEmpty()) {
            $menu->setIsActif(false);
            $this->em->flush();
            return 'Ce menu est lié à des commandes passées : il a été désactivé du catalogue.';
        }

        $this->em->remove($menu);
        $this->em->flush();
        return 'Menu supprimé définitivement.';
    }

    private function hydraterPlat(Plat $plat, array $data): void
    {
        if (isset($data['nom'])) {
            $plat->setNom(trim($data['nom']));
        }
        if (isset($data['description'])) {
            $plat->setDescription(trim($data['description']));
        }
        if (isset($data['type'])) {
            $plat->setType(trim($data['type']));
        }
        if (isset($data['allergenes'])) {
            $plat->setAllergenes(trim($data['allergenes']));
        }
        if (isset($data['stock'])) {
            $plat->setStock(max(0, (int) $data['stock']));
        }
        if (isset($data['seuilAlerte'])) {
            $plat->setSeuilAlerte(max(1, (int) $data['seuilAlerte']));
        }
    }

    private function hydraterMenu(Menu $menu, array $data): void
    {
        $menu->setTitre(trim((string) ($data['titre'] ?? $menu->getTitre() ?? '')));
        $menu->setDescription(trim((string) ($data['description'] ?? $menu->getDescription() ?? '')));

        $theme = trim((string) ($data['theme'] ?? ''));
        $menu->setTheme($theme !== '' ? $theme : ($menu->getTheme() ?? 'Classique'));

        $regime = trim((string) ($data['regime'] ?? ''));
        $menu->setRegime($regime !== '' ? $regime : ($menu->getRegime() ?? 'Carnivore'));

        if (array_key_exists('image', $data)) {
            $urlImage = trim((string) $data['image']);
            $menu->setImage($urlImage !== '' ? $urlImage : null);
        }

        $menu->setConditionsMenu($data['conditionsMenu'] ?? $data['conditions'] ?? $menu->getConditionsMenu());

        if (isset($data['prixParPersonne'])) {
            $menu->setPrixParPersonne((string) max(1, (float) $data['prixParPersonne']));
        }
        if (isset($data['nbPersonnesMin'])) {
            $menu->setNbPersonnesMin(max(1, (int) $data['nbPersonnesMin']));
        }
        if (isset($data['quantiteStock'])) {
            $menu->setQuantiteStock(max(0, (int) $data['quantiteStock']));
        }

        $menu->setIsActif((bool) ($data['isActif'] ?? $menu->isIsActif() ?? true));

        if (isset($data['platIds']) && is_array($data['platIds'])) {
            $this->synchroniserPlatsMenu($menu, $data['platIds']);
        }
    }

    private function synchroniserPlatsMenu(Menu $menu, array $platIds): void
    {
        foreach ($menu->getPlats()->toArray() as $existingPlat) {
            $menu->removePlat($existingPlat);
        }

        foreach ($platIds as $platId) {
            $plat = $this->platRepository->find((int) $platId);
            if ($plat) {
                $menu->addPlat($plat);
            }
        }
    }

    private function formatPlat(Plat $plat): array
    {
        $rawAllergenes = $plat->getAllergenes();
        $allergenesList = $rawAllergenes
            ? array_values(array_filter(array_map('trim', explode(',', $rawAllergenes))))
            : [];

        return [
            'id'            => $plat->getId(),
            'nom'           => $plat->getNom(),
            'description'   => $plat->getDescription(),
            'type'          => $plat->getType(),
            'allergenes'    => $allergenesList,
            'allergenesRaw' => $rawAllergenes ?? '',
            'stock'         => $plat->getStock(),
            'seuilAlerte'   => $plat->getSeuilAlerte(),
            'isEnRupture'   => $plat->isEnRupture(),
            'isStockFaible' => $plat->isStockFaible(),
        ];
    }

    private function formatMenu(Menu $menu): array
    {
        $platsData = [];
        $allergenesSet = [];

        foreach ($menu->getPlats() as $plat) {
            $formatted = $this->formatPlat($plat);
            $platsData[] = $formatted;
            foreach ($formatted['allergenes'] as $alg) {
                $allergenesSet[] = $alg;
            }
        }

        $imageUrl = $menu->getImage() ?: self::DEFAULT_IMAGE;

        return [
            'id'                => $menu->getId(),
            'titre'             => $menu->getTitre(),
            'description'       => $menu->getDescription(),
            'conditions'        => $menu->getConditionsMenu(),
            'conditionsMenu'    => $menu->getConditionsMenu(),
            'prixParPersonne'   => (float) $menu->getPrixParPersonne(),
            'nombrePersonneMin' => $menu->getNbPersonnesMin(),
            'nbPersonnesMin'    => $menu->getNbPersonnesMin(),
            'quantiteStock'     => $menu->getQuantiteStock(),
            'stockDisponible'   => $menu->getQuantiteStock(),
            'isActif'           => $menu->isIsActif(),
            'theme'             => $menu->getTheme() ?: 'Classique',
            'regime'            => $menu->getRegime() ?: 'Carnivore',
            'image'             => $imageUrl,
            'images'            => [$imageUrl],
            'plats'             => $platsData,
            'allergenes'        => array_values(array_unique($allergenesSet)),
        ];
    }
}
