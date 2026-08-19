<?php

namespace App\Services\Mcp;

use App\Services\Mcp\Skills\VectorSimilaritySkill;
use App\Services\Mcp\Skills\QrIdentitySkill;
use App\Services\Mcp\Skills\GracePeriodValidatorSkill;
use App\Services\Mcp\Skills\AdoptionCompatibilitySkill;
use App\Services\Mcp\Skills\NlpExtractionSkill;

class McpToolRegistry
{
    protected array $tools = [];

    public function __construct(
        VectorSimilaritySkill $vectorSimilaritySkill,
        QrIdentitySkill $qrIdentitySkill,
        GracePeriodValidatorSkill $gracePeriodValidatorSkill,
        AdoptionCompatibilitySkill $adoptionCompatibilitySkill,
        NlpExtractionSkill $nlpExtractionSkill
    ) {
        $this->tools = [
            'skill_extraer_entidades_nlp' => $nlpExtractionSkill,
            'skill_buscar_similitud_vectorial' => $vectorSimilaritySkill,
            'skill_generar_identidad_qr' => $qrIdentitySkill,
            'skill_verificar_periodo_gracia' => $gracePeriodValidatorSkill,
            'skill_evaluar_compatibilidad_adopcion' => $adoptionCompatibilitySkill,
        ];
    }

    public function getToolsManifest(): array
    {
        $manifest = [];
        foreach ($this->tools as $name => $toolInstance) {
            $manifest[] = [
                'name' => $name,
                'description' => $toolInstance->getDescription(),
                'parameters' => $toolInstance->getParametersSchema(),
            ];
        }
        return $manifest;
    }

    public function getTool(string $name)
    {
        return $this->tools[$name] ?? null;
    }
}
