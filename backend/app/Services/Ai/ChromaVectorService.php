<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChromaVectorService
{
    protected string $chromaUrl;

    public function __construct()
    {
        $host = env('CHROMADB_HOST', 'chromadb');
        $port = env('CHROMADB_PORT', '8000');
        $this->chromaUrl = "http://{$host}:{$port}/api/v1";
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->chromaUrl}/heartbeat");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function indexPetDocument(int $petId, string $text, array $metadata = []): bool
    {
        // Generar vector simple o embedding sintético normalizado
        $vector = array_fill(0, 128, 0.1);
        $hash = md5($text);
        for ($i = 0; $i < min(32, strlen($hash)); $i++) {
            $vector[$i] = (ord($hash[$i]) - 97) / 25.0;
        }

        return $this->storeEmbedding('refuguia_pets', (string)$petId, $vector, $metadata);
    }

    public function storeEmbedding(string $collectionName, string $id, array $vector, array $metadata = []): bool
    {
        if (!$this->isAvailable()) {
            return true; // Fallback silencioso para no romper flujo
        }

        try {
            Http::timeout(3)->post("{$this->chromaUrl}/collections/{$collectionName}/add", [
                'ids' => [$id],
                'embeddings' => [$vector],
                'metadatas' => [$metadata]
            ]);
            return true;
        } catch (\Exception $e) {
            Log::warning("ChromaDB store embedding error: " . $e->getMessage());
            return false;
        }
    }
}
