<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class MongoStatistiquesService
{
    private mixed $client = null;
    private string $database = 'vite_et_gourmand_analytics';

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

    /**
     * Enregistre ou met à jour une commande dans la collection NoSQL
     */
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
                        'menu_titre'      => $data['menuTitre'],
                        'prix_total'      => (float) $data['prixTotal'],
                        'nb_personnes'    => (int) $data['nbPersonnes'],
                        'statut'          => mb_strtolower(trim($data['statut'])),
                        'date_commande'   => date('c')
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
     * Calcule le chiffre d'affaires et les volumes via un pipeline d'agrégation NoSQL
     */
    public function getStatistiquesVentes(): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $collection = $this->client->selectDatabase($this->database)->selectCollection('commandes_analytics');
            $cursor = $collection->aggregate([
                [
                    '$match' => [
                        'statut' => ['$in' => ['livré', 'livre', 'terminée', 'terminee', 'cloturee', 'clôturée', 'accepté', 'accepte']]
                    ]
                ],
                [
                    '$group' => [
                        '_id'             => '$menu_titre',
                        'chiffreAffaires' => ['$sum' => '$prix_total'],
                        'totalCommandes'  => ['$sum' => 1],
                        'totalConvives'   => ['$sum' => '$nb_personnes']
                    ]
                ],
                [
                    '$sort' => ['chiffreAffaires' => -1]
                ]
            ]);

            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    '_id'             => $doc['_id'],
                    'chiffreAffaires' => (float) $doc['chiffreAffaires'],
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
     * Indicateurs globaux (CA Total, Nombre total de commandes, total convives)
     */
    public function getIndicateursGlobaux(): array
    {
        if ($this->client === null) {
            return ['caTotal' => 0.0, 'nbCommandes' => 0, 'totalConvives' => 0];
        }

        try {
            $collection = $this->client->selectDatabase($this->database)->selectCollection('commandes_analytics');
            $cursor = $collection->aggregate([
                [
                    '$match' => [
                        'statut' => ['$in' => ['livré', 'livre', 'terminée', 'terminee', 'cloturee', 'clôturée', 'accepté', 'accepte']]
                    ]
                ],
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
}
