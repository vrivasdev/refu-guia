<?php

namespace App\Services\Mcp;

use App\Services\Mcp\Skills\NlpExtractionSkill;
use App\Services\Mcp\Skills\VectorSimilaritySkill;
use App\Services\Mcp\Skills\QrIdentitySkill;
use App\Services\Mcp\Skills\GracePeriodValidatorSkill;
use App\Services\Mcp\Skills\AdoptionCompatibilitySkill;
use Illuminate\Support\Facades\File;

class McpToolRegistry
{
    protected array $skills = [];

    public function __construct(
        NlpExtractionSkill $nlpSkill,
        VectorSimilaritySkill $vectorSkill,
        QrIdentitySkill $qrSkill,
        GracePeriodValidatorSkill $graceSkill,
        AdoptionCompatibilitySkill $adoptionSkill
    ) {
        $this->skills = [
            'skill_extraer_entidades_nlp' => $nlpSkill,
            'skill_calcular_similitud_vectorial' => $vectorSkill,
            'skill_generar_identidad_qr' => $qrSkill,
            'skill_verificar_periodo_gracia' => $graceSkill,
            'skill_evaluar_compatibilidad_adopcion' => $adoptionSkill,
        ];
    }

    public function getToolsManifest(): array
    {
        $tools = [];
        $skillsDir = storage_path('app/skills');

        foreach ($this->skills as $name => $instance) {
            $mdPath = "{$skillsDir}/{$name}.md";
            $mdContent = File::exists($mdPath) ? File::get($mdPath) : null;

            $tools[] = [
                'name' => $name,
                'description' => match($name) {
                    'skill_extraer_entidades_nlp' => 'Extrae entidades estructuradas (especie, raza, colores, tamaño, salud) a partir de lenguaje natural.',
                    'skill_calcular_similitud_vectorial' => 'Calcula la similitud semántica y geoespacial (Haversine) entre mascotas perdidas y encontradas.',
                    'skill_generar_identidad_qr' => 'Genera el UUID de emergencia y el payload encriptado del código QR para collares de refugio.',
                    'skill_verificar_periodo_gracia' => 'Valida la regla legal inamovible de los 15 días continuos de búsqueda familiar post-sismo.',
                    'skill_evaluar_compatibilidad_adopcion' => 'Evalúa solicitudes de adopción aplicando Hard-Stops de presupuesto, vivienda y convivencia.',
                    default => 'Herramienta MCP de RefuGuia'
                },
                'parameters' => [
                    'type' => 'object',
                    'required' => []
                ],
                'definition_source' => "storage/app/skills/{$name}.md",
                'has_markdown_doc' => !is_null($mdContent),
                'markdown_spec' => $mdContent
            ];
        }

        return $tools;
    }

    public function getTool(string $name)
    {
        return $this->skills[$name] ?? null;
    }

    public function getSkill(string $name)
    {
        return $this->getTool($name);
    }
}
