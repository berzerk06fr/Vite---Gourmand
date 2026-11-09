<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class MongoAuditService
{
    private mixed $client = null;
    private string $databaseName;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?string $database = null
    ) {
        $this->databaseName = $database ?? 'vite_et_gourmand_nosql';

        if (extension_loaded('mongodb') && class_exists(\MongoDB\Client::class)) {
            try {
                $this->client = new \MongoDB\Client('mongodb://127.0.0.1:27017');
            } catch (\Throwable $e) {
                $this->logger->warning('Erreur connexion MongoDB : ' . $e->getMessage());
            }
        }
    }

    /**
     * Enregistre un document d'audit dans la collection NoSQL cible.
     */
    public function log(string $collection, array $data): bool
    {
        $payload = array_merge($data, [
            'logged_at' => (new \DateTimeImmutable())->format('c'),
            'moteur'    => 'NoSQL/MongoDB',
        ]);

        if ($this->client === null) {
            $this->logger->info(sprintf('[NoSQL Audit Document] Collection "%s": %s', $collection, json_encode($payload)));
            return false;
        }

        try {
            $this->client->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->insertOne($payload);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur écriture MongoDB: ' . $e->getMessage());
            return false;
        }
    }
}
