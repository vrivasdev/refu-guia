<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChromaVectorService
{
    protected string $host;
    protected string $collectionName = 'pets_emergency_collection';
    protected ?string $collectionId = null;

    public function __construct()
    {
        $this->host = env('CHROMA_HOST', 'http://chromadb:8000');
        $this->ensureCollection();
    }

    protected function getBasePath(): string
    {
        return "{$this->host}/api/v2/tenants/default_tenant/databases/default_database/collections";
    }

    public function ensureCollection(): void
    {
        try {
            $url = $this->getBasePath();
            $res = Http::timeout(3)->get($url);

            if ($res->successful()) {
                $collections = $res->json();
                foreach ($collections as $col) {
                    if (($col['name'] ?? '') === $this->collectionName) {
                        $this->collectionId = $col['id'];
                        return;
                    }
                }

                // Crear la colección si no existe
                $createRes = Http::timeout(3)->post($url, [
                    'name' => $this->collectionName,
                    'metadata' => [
                        'description' => 'Colección vectorial de mascotas post-sismo',
                        'dimension' => 1536,
                        'created_by' => 'RefuGuía AI Core'
                    ]
                ]);

                if ($createRes->successful()) {
                    $this->collectionId = $createRes->json('id');
                }
            }
        } catch (\Exception $e) {
            Log::warning("ChromaDB v2 collection init warning: " . $e->getMessage());
        }
    }

    public function generateEmbedding(string $text): array
    {
        $hash = hash('sha256', mb_strtolower(trim($text)));
        $vector = [];
        for ($i = 0; $i < 384; $i++) {
            $sub = substr($hash, ($i % 60), 4);
            $val = (hexdec($sub) / 65535.0) * 2.0 - 1.0;
            $vector[] = round($val, 6);
        }
        return $vector;
    }

    public function indexPetDocument(int $petId, string $documentText, array $metadata = []): bool
    {
        $this->ensureCollection();
        $embedding = $this->generateEmbedding($documentText);

        if (!$this->collectionId) {
            return false;
        }

        try {
            $url = "{$this->getBasePath()}/{$this->collectionId}/add";
            $res = Http::timeout(4)->post($url, [
                'ids' => [(string)$petId],
                'embeddings' => [$embedding],
                'documents' => [$documentText],
                'metadatas' => [array_merge($metadata, ['pet_id' => $petId, 'timestamp' => time()])]
            ]);

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning("ChromaDB v2 indexing failed: " . $e->getMessage());
            return false;
        }
    }

    public function getCollectionData(): array
    {
        $this->ensureCollection();
        if (!$this->collectionId) return ['ids' => [], 'documents' => [], 'metadatas' => []];

        try {
            $url = "{$this->getBasePath()}/{$this->collectionId}/get";
            $res = Http::timeout(3)->post($url, [
                'include' => ['documents', 'metadatas']
            ]);
            if ($res->successful()) {
                return $res->json();
            }
        } catch (\Exception $e) {}

        return ['ids' => [], 'documents' => [], 'metadatas' => []];
    }

    public function getHealth(): array
    {
        try {
            $res = Http::timeout(2)->get("{$this->host}/api/v2/heartbeat");
            $this->ensureCollection();
            return [
                'online' => $res->successful(),
                'host' => $this->host,
                'api_version' => 'v2 (ChromaDB 0.5+)',
                'collection' => $this->collectionName,
                'collection_id' => $this->collectionId
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'host' => $this->host,
                'error' => $e->getMessage()
            ];
        }
    }
}
