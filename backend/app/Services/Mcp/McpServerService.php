<?php

namespace App\Services\Mcp;

use App\Services\Mcp\Skills\GracePeriodValidatorSkill;
use App\Services\Mcp\Skills\AdoptionCompatibilitySkill;
use App\Services\Mcp\Skills\QrIdentitySkill;
use App\Services\Mcp\Skills\NlpExtractionSkill;
use App\Services\Mcp\Skills\VectorSimilaritySkill;
use App\Services\Mcp\Skills\VisionPeritajeSkill;
use App\Services\Ai\LocalVisionService;
use App\Services\Ai\ChromaVectorService;
use Illuminate\Support\Facades\File;
use RuntimeException;

class McpServerService
{
    protected array $skills = [];

    public function __construct(
        ChromaVectorService $chroma,
        LocalVisionService $vision
    ) {
        $this->skills = [
            'skill_verificar_periodo_gracia' => new GracePeriodValidatorSkill(),
            'skill_evaluar_compatibilidad_adopcion' => new AdoptionCompatibilitySkill(),
            'skill_generar_identidad_qr' => new QrIdentitySkill(),
            'skill_extraer_entidades_nlp' => new NlpExtractionSkill(),
            'skill_calcular_similitud_vectorial' => new VectorSimilaritySkill($chroma),
            'skill_peritaje_visual_moondream' => new VisionPeritajeSkill($vision),
        ];
    }

    public function getRegisteredTools(): array
    {
        $tools = [];
        $skillsDir = storage_path('app/skills');

        $agentMap = [
            'skill_verificar_periodo_gracia' => 'Agente_Auditor_Legal',
            'skill_evaluar_compatibilidad_adopcion' => 'Agente_Triaje_Adopcion',
            'skill_generar_identidad_qr' => 'Agente_Trazabilidad_QR',
            'skill_extraer_entidades_nlp' => 'Agente_NLP_Ingesta',
            'skill_calcular_similitud_vectorial' => 'Agente_Matchmaker_Vectorial',
            'skill_peritaje_visual_moondream' => 'Agente_Peritaje_Visual_VLM'
        ];

        foreach ($this->skills as $name => $instance) {
            $mdPath = "{$skillsDir}/{$name}.md";
            $mdContent = File::exists($mdPath) ? File::get($mdPath) : null;

            $description = method_exists($instance, 'getDescription') ? $instance->getDescription() : 'Herramienta MCP de RefuGuía';
            $params = method_exists($instance, 'getParametersSchema') ? $instance->getParametersSchema() : [];
            $agent = $agentMap[$name] ?? 'Agente_Refugio';

            $tools[] = [
                'name' => $name,
                'description' => $description,
                'parameters' => $params,
                'target_agent' => $agent,
                'definition_source' => "storage/app/skills/{$name}.md",
                'has_markdown_doc' => !is_null($mdContent),
                'markdown_spec' => $mdContent
            ];
        }

        return $tools;
    }

    public function listTools(): array
    {
        return $this->getRegisteredTools();
    }

    public function getSkill(string $name)
    {
        return $this->skills[$name] ?? null;
    }

    public function executeTool(string $toolName, array $params, string $callingAgent = 'Agent_Supervisor'): array
    {
        if (!isset($this->skills[$toolName])) {
            throw new RuntimeException("Herramienta/Skill MCP '{$toolName}' no encontrada en el catálogo.");
        }

        $skill = $this->skills[$toolName];
        $result = $skill->execute($params);

        return [
            'success' => true,
            'mcp_protocol_version' => '2026.1',
            'calling_agent' => $callingAgent,
            'dispatched_skill' => $toolName,
            'timestamp' => now()->toIso8601String(),
            'data' => $result['data'] ?? $result
        ];
    }
}
