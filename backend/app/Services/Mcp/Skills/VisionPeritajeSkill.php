<?php

namespace App\Services\Mcp\Skills;

use App\Models\Pet;
use App\Services\Ai\LocalVisionService;

class VisionPeritajeSkill
{
    protected LocalVisionService $visionService;

    public function __construct(LocalVisionService $visionService)
    {
        $this->visionService = $visionService;
    }

    public function getName(): string
    {
        return 'skill_peritaje_visual_moondream';
    }

    public function getDescription(): string
    {
        return 'Peritaje visual anatómico y cotejo multimodal de fotografías de mascotas post-sismo utilizando el modelo VLM local Moondream.';
    }

    public function getTargetAgent(): string
    {
        return 'Agente_Peritaje_Visual_VLM';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lost_pet_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la mascota perdida.'
                ],
                'found_pet_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la mascota rescatada.'
                ],
                'photo_lost_url' => [
                    'type' => 'string',
                    'description' => 'URL o Base64 de la fotografía 1 (familia).'
                ],
                'photo_found_url' => [
                    'type' => 'string',
                    'description' => 'URL o Base64 de la fotografía 2 (refugio).'
                ]
            ],
            'required' => ['lost_pet_id', 'found_pet_id']
        ];
    }

    public function execute(array $params): array
    {
        $lostPetId = $params['lost_pet_id'] ?? null;
        $foundPetId = $params['found_pet_id'] ?? null;

        $lostPet = $lostPetId ? Pet::find($lostPetId) : null;
        $foundPet = $foundPetId ? Pet::find($foundPetId) : null;

        $photo1 = $params['photo_lost_url'] ?? ($lostPet ? $lostPet->photo_url : '');
        $photo2 = $params['photo_found_url'] ?? ($foundPet ? $foundPet->photo_url : '');

        $context = "Cotejo de rescate post-sismo. Mascota 1: " . ($lostPet ? $lostPet->name : 'Reporte Extraviado') . 
                   " vs Mascota 2: " . ($foundPet ? $foundPet->name : 'Ingreso Refugio');

        $vlmResult = $this->visionService->comparePetPhotos($photo1, $photo2, $context);

        return [
            'success' => true,
            'tool_name' => $this->getName(),
            'agent' => $this->getTargetAgent(),
            'data' => [
                'lost_pet_id' => $lostPetId,
                'found_pet_id' => $foundPetId,
                'visual_similarity_score' => $vlmResult['visual_similarity_score'],
                'engine' => $vlmResult['engine_used'],
                'anatomical_verdict' => $vlmResult['anatomical_rationale'],
                'features_evaluated' => $vlmResult['features_evaluated'],
                'confidence_tier' => $vlmResult['visual_similarity_score'] >= 85 ? 'HIGH_CONFIDENCE_MATCH' : 'REQUIRES_MANUAL_REVIEW',
                'telemetry' => $vlmResult['telemetry']
            ]
        ];
    }
}
