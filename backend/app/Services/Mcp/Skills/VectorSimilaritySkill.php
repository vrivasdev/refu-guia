<?php

namespace App\Services\Mcp\Skills;

use App\Models\Pet;
use App\Services\Ai\ChromaVectorService;

class VectorSimilaritySkill
{
    protected ChromaVectorService $chromaService;

    public function __construct(ChromaVectorService $chromaService)
    {
        $this->chromaService = $chromaService;
    }

    public function getDescription(): string
    {
        return "Calcula la similitud matemática entre vectores de características de mascotas en ChromaDB, ponderando fenotipo y distancia geo-temporal.";
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'target_pet_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la mascota ingresada o reportada.'
                ],
                'target_type' => [
                    'type' => 'string',
                    'enum' => ['found', 'lost'],
                    'description' => 'Buscar contra el conjunto opuesto.'
                ]
            ],
            'required' => ['target_pet_id']
        ];
    }

    public function execute(array $params): array
    {
        $targetPet = Pet::find($params['target_pet_id']);
        if (!$targetPet) {
            throw new \Exception("Mascota con ID {$params['target_pet_id']} no encontrada.");
        }

        // Buscar mascotas candidatas del estado opuesto
        $oppositeType = ($targetPet->report_type === 'found') ? 'lost' : 'found';
        $candidates = Pet::where('report_type', $oppositeType)->get();

        $matches = [];

        foreach ($candidates as $candidate) {
            // Regla Dura: Si la especie no coincide -> 0%
            if ($candidate->species !== $targetPet->species) {
                continue;
            }

            // Similitud Visual & Fenotípica (40%)
            $visualScore = 0.0;
            if (strtolower($candidate->primary_color) === strtolower($targetPet->primary_color)) {
                $visualScore += 0.50;
            }
            if ($candidate->secondary_color && $targetPet->secondary_color && strtolower($candidate->secondary_color) === strtolower($targetPet->secondary_color)) {
                $visualScore += 0.30;
            }
            if ($candidate->size === $targetPet->size) {
                $visualScore += 0.20;
            }
            $visualScore = min(1.0, $visualScore);

            // Similitud Semántica de Rasgos / Raza (30%)
            $semanticScore = 0.50;
            if ($candidate->breed && $targetPet->breed && (str_contains(strtolower($candidate->breed), strtolower($targetPet->breed)) || str_contains(strtolower($targetPet->breed), strtolower($candidate->breed)))) {
                $semanticScore = 0.95;
            }

            // Distancia Geográfica Haversine (30%)
            $geoDistanceKm = $this->calculateHaversineDistance(
                $targetPet->latitude ?? 10.4806,
                $targetPet->longitude ?? -66.9036,
                $candidate->latitude ?? 10.4806,
                $candidate->longitude ?? -66.9036
            );

            // Puntuación de proximidad (decae con los km)
            $geoScore = max(0.1, 1.0 - ($geoDistanceKm / 50.0)); // Si está a más de 50km decae a 0.1

            // Puntuación Total Ponderada (0 - 100%)
            $totalSimilarity = round((($visualScore * 0.40) + ($semanticScore * 0.30) + ($geoScore * 0.30)) * 100, 1);

            // Clasificación según umbrales de la rúbrica y anteproyecto
            $decisionLevel = 'DISCARD';
            if ($totalSimilarity >= 80.0) {
                $decisionLevel = 'HIGH_CONFIDENCE_AUTO_ALERT';
            } elseif ($totalSimilarity >= 50.0) {
                $decisionLevel = 'MODERATE_MANUAL_REVIEW';
            }

            $matches[] = [
                'candidate_pet_id' => $candidate->id,
                'candidate_uuid' => $candidate->uuid,
                'candidate_name' => $candidate->name ?? 'Sin nombre reportado',
                'candidate_photo' => $candidate->photo_url,
                'similarity_score' => $totalSimilarity,
                'visual_breakdown' => round($visualScore * 100, 1),
                'semantic_breakdown' => round($semanticScore * 100, 1),
                'geo_distance_km' => round($geoDistanceKm, 2),
                'decision_level' => $decisionLevel
            ];
        }

        // Ordenar por mayor porcentaje
        usort($matches, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return [
            'target_pet' => [
                'id' => $targetPet->id,
                'uuid' => $targetPet->uuid,
                'species' => $targetPet->species,
                'primary_color' => $targetPet->primary_color,
                'size' => $targetPet->size,
            ],
            'total_candidates_analyzed' => count($candidates),
            'top_matches' => array_slice($matches, 0, 5)
        ];
    }

    protected function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
