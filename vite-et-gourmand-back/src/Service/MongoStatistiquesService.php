<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class MongoStatistiquesService
{
    private mixed $client = null;
    private string $database = 'vite_et_gourmand_analytics';

    private const STATUTS_VALIDES = [
        'livré', 'livre', 'terminée', 'terminee', 'cloturee', 'clôturée', 'accepté', 'accepte'
    ];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        $mongoUri = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL') ?: 'mongodb://127.0.0.1:27017';

        if (extension_loaded('mongodb') && class_exists(\MongoDB\Client::class)) {
            try {
                $this->client = new \MongoDB\Client($mongoUri);
            } catch (\Throwable $e) {
                $this->logger->warning('Erreur connexion MongoDB Analytics : ' . $e->getMessage());
            }
        }
    }

    public function synchroniserCommande(array $data): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $collection = $this->client->selectDatabase($this->database)->selectCollection('commandes_analytics');
            $collection->updateOne(
                ['commande_id' => (int) $data['id']],
                [
                    '$set' => [
                        'commande_id'     => (int) $data['id'],
                        'numero_commande' => $data['numeroCommande'],
                        'menu_id'         => (int) ($data['menuId'] ?? 0),
                        'menu_titre'      => $data['menuTitre'],
                        'prix_total'      => (float) $data['prixTotal'],
                        'nb_personnes'    => (int) $data['nbPersonnes'],
                        'statut'          => mb_strtolower(trim($data['statut'])),
                        'date_commande'   => $data['dateCommande'] ?? date('Y-m-d'),
                    ]
                ],
                ['upsert' => true]
            );
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur synchronisation MongoDB Analytics : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule le chiffre d'affaires et le nombre de commandes par menu avec filtres optionnels
     */
    public function getStatistiquesVentes(?string $menuTitre = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $collection = $this->client->selectDatabase($this->database)->selectCollection('commandes_analytics');
            $match = $this->construireFiltreMatch($menuTitre, $dateDebut, $dateFin);

            $cursor = $collection->aggregate([
                ['$match' => $match],
                [
                    '$group' => [
                        '_id'             => '$menu_titre',
                        'menuId'          => ['$first' => '$menu_id'],
                        'chiffreAffaires' => ['$sum' => '$prix_total'],
                        'totalCommandes'  => ['$sum' => 1],
                        'totalConvives'   => ['$sum' => '$nb_personnes']
                    ]
                ],
                ['$sort' => ['totalCommandes' => -1]]
            ]);

            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    '_id'             => $doc['_id'],
                    'menuId'          => (int) ($doc['menuId'] ?? 0),
                    'chiffreAffaires' => round((float) $doc['chiffreAffaires'], 2),
                    'totalCommandes'  => (int) $doc['totalCommandes'],
                    'totalConvives'   => (int) $doc['totalConvives']
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lecture statistiques MongoDB : ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Indicateurs globaux filtrés (CA Total, Nombre total de commandes, total convives)
     */
    public function getIndicateursGlobaux(?string $menuTitre = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        if ($this->client === null) {
            return ['caTotal' => 0.0, 'nbCommandes' => 0, 'totalConvives' => 0];
        }

        try {
            $collection = $this->client->selectDatabase($this->database)->selectCollection('commandes_analytics');
            $match = $this->construireFiltreMatch($menuTitre, $dateDebut, $dateFin);

            $cursor = $collection->aggregate([
                ['$match' => $match],
                [
                    '$group' => [
                        '_id'           => null,
                        'caTotal'       => ['$sum' => '$prix_total'],
                        'nbCommandes'   => ['$sum' => 1],
                        'totalConvives' => ['$sum' => '$nb_personnes']
                    ]
                ]
            ]);

            $data = iterator_to_array($cursor);

            if (!empty($data) && isset($data[0])) {
                return [
                    'caTotal'       => round((float) $data[0]['caTotal'], 2),
                    'nbCommandes'   => (int) $data[0]['nbCommandes'],
                    'totalConvives' => (int) $data[0]['totalConvives']
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur indicateurs globaux MongoDB : ' . $e->getMessage());
        }

        return ['caTotal' => 0.0, 'nbCommandes' => 0, 'totalConvives' => 0];
    }

    private function construireFiltreMatch(?string $menuTitre, ?string $dateDebut, ?string $dateFin): array
    {
        $match = [
            'statut' => ['$in' => self::STATUTS_VALIDES]
        ];

        if ($menuTitre !== null && trim($menuTitre) !== '') {
            $match['menu_titre'] = trim($menuTitre);
        }

        if (($dateDebut !== null && $dateDebut !== '') || ($dateFin !== null && $dateFin !== '')) {
            $dateFilter = [];
            if ($dateDebut !== null && $dateDebut !== '') {
                $dateFilter['$gte'] = substr($dateDebut, 0, 10);
            }
            if ($dateFin !== null && $dateFin !== '') {
                $dateFilter['$lte'] = substr($dateFin, 0, 10);
            }
            $match['date_commande'] = $dateFilter;
        }

        return $match;
    }
}
