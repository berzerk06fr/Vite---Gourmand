<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class MongoStatistiqueService
{
    private string $container = 'vg_mongodb';
    private string $database = 'vite_et_gourmand_analytics';

    /**
     * Enregistre ou met à jour une commande dans la collection NoSQL
     */
    public function synchroniserCommande(array $data): bool
    {
        $json = json_encode([
            'commande_id'     => $data['id'],
            'numero_commande' => $data['numeroCommande'],
            'menu_titre'      => $data['menuTitre'],
            'prix_total'      => (float) $data['prixTotal'],
            'nb_personnes'    => (int) $data['nbPersonnes'],
            'statut'          => $data['statut'],
            'date_commande'   => date('c')
        ]);

        $query = sprintf(
            'db.commandes_analytics.updateOne({ commande_id: %d }, { $set: %s }, { upsert: true })',
            (int) $data['id'],
            $json
        );

        return $this->executerQuery($query) !== null;
    }

    /**
     * Calcule le chiffre d'affaires et les volumes via un pipeline d'agrégation NoSQL
     */
    public function getStatistiquesVentes(): array
    {
        $pipeline = json_encode([
            [
                '$match' => [
                    'statut' => ['$in' => ['livré', 'accepté', 'en cours']]
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

        $query = sprintf('JSON.stringify(db.commandes_analytics.aggregate(%s).toArray())', $pipeline);
        $res = $this->executerQuery($query);

        return $res ? json_decode($res, true) ?? [] : [];
    }

    /**
     * Indicateurs globaux (CA Total, Nombre total de commandes livrées/acceptées)
     */
    public function getIndicateursGlobaux(): array
    {
        $pipeline = json_encode([
            [
                '$match' => [
                    'statut' => ['$in' => ['livré', 'accepté']]
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

        $query = sprintf('JSON.stringify(db.commandes_analytics.aggregate(%s).toArray())', $pipeline);
        $res = $this->executerQuery($query);
        $data = $res ? json_decode($res, true) : [];

        if (!empty($data)) {
            return [
                'caTotal'       => round((float) $data[0]['caTotal'], 2),
                'nbCommandes'   => (int) $data[0]['nbCommandes'],
                'totalConvives' => (int) $data[0]['totalConvives']
            ];
        }

        return ['caTotal' => 0, 'nbCommandes' => 0, 'totalConvives' => 0];
    }

    private function executerQuery(string $mongoScript): ?string
    {
        $cmd = sprintf(
            'docker exec %s mongosh %s --quiet --eval %s',
            escapeshellarg($this->container),
            escapeshellarg($this->database),
            escapeshellarg($mongoScript)
        );

        $process = Process::fromShellCommandline($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }
}
