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

    protected function parseMarkdownSkillFile(string $filePath): ?array
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $pattern = '/^---[\r\n]+(.*?)[\r\n]+---[\r\n]+(.*)$/s';

        if (preg_match($pattern, $content, $matches)) {
            $frontmatterRaw = $matches[1];
            $markdownBody = trim($matches[2]);

            $meta = [];
            $lines = explode("\n", $frontmatterRaw);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (str_contains($line, ':')) {
                    $parts = explode(':', $line, 2);
                    $key = trim($parts[0]);
                    $val = trim($parts[1] ?? '', " \"'\t\n\r");
                    if (!empty($val)) {
                        $meta[$key] = $val;
                    }
                }
            }

            return [
                'name' => $meta['name'] ?? basename($filePath, '.md'),
                'version' => $meta['version'] ?? '1.0.0',
                'category' => $meta['category'] ?? 'General',
                'description' => $meta['description'] ?? '',
                'author' => $meta['author'] ?? 'RefuGuía AI Core',
                'timeout_ms' => (int)($meta['timeout_ms'] ?? 3000),
                'markdown_body' => $markdownBody,
                'raw_content' => $content
            ];
        }

        return [
            'name' => basename($filePath, '.md'),
            'version' => '1.0.0',
            'category' => 'General',
            'description' => 'Skill de RefuGuía',
            'author' => 'RefuGuía AI Core',
            'timeout_ms' => 3000,
            'markdown_body' => $content,
            'raw_content' => $content
        ];
    }

    public function getToolsManifest(): array
    {
        $tools = [];
        $skillsDir = storage_path('app/skills');

        foreach ($this->skills as $name => $instance) {
            $mdPath = "{$skillsDir}/{$name}.md";
            $parsedMd = $this->parseMarkdownSkillFile($mdPath);

            $parameters = match($name) {
                'skill_extraer_entidades_nlp' => [
                    'type' => 'object',
                    'required' => ['raw_text'],
                    'properties' => [
                        'raw_text' => ['type' => 'string', 'description' => 'Texto libre del reporte.']
                    ]
                ],
                'skill_calcular_similitud_vectorial' => [
                    'type' => 'object',
                    'required' => ['lost_pet_id', 'found_pet_id'],
                    'properties' => [
                        'lost_pet_id' => ['type' => 'integer', 'description' => 'ID mascota perdida.'],
                        'found_pet_id' => ['type' => 'integer', 'description' => 'ID mascota encontrada.']
                    ]
                ],
                'skill_generar_identidad_qr' => [
                    'type' => 'object',
                    'required' => ['pet_id'],
                    'properties' => [
                        'pet_id' => ['type' => 'integer', 'description' => 'ID mascota.']
                    ]
                ],
                'skill_verificar_periodo_gracia' => [
                    'type' => 'object',
                    'required' => ['pet_id'],
                    'properties' => [
                        'pet_id' => ['type' => 'integer', 'description' => 'ID mascota.']
                    ]
                ],
                'skill_evaluar_compatibilidad_adopcion' => [
                    'type' => 'object',
                    'required' => ['pet_id', 'monthly_income_usd', 'housing_type', 'hours_dedicated_daily'],
                    'properties' => [
                        'pet_id' => ['type' => 'integer', 'description' => 'ID mascota'],
                        'monthly_income_usd' => ['type' => 'number', 'description' => 'Ingreso mensual.'],
                        'housing_type' => ['type' => 'string', 'description' => 'Tipo vivienda.'],
                        'hours_dedicated_daily' => ['type' => 'integer', 'description' => 'Horas cuidado.']
                    ]
                ],
                default => [
                    'type' => 'object',
                    'required' => [],
                    'properties' => []
                ]
            };

            $tools[] = [
                'name' => $name,
                'version' => $parsedMd['version'] ?? '1.0.0',
                'category' => $parsedMd['category'] ?? 'Procesamiento de IA',
                'description' => $parsedMd['description'] ?? 'Skill de RefuGuia',
                'author' => $parsedMd['author'] ?? 'RefuGuía AI Core',
                'timeout_ms' => $parsedMd['timeout_ms'] ?? 3000,
                'parameters' => $parameters,
                'definition_source' => "storage/app/skills/{$name}.md",
                'has_markdown_doc' => !empty($parsedMd['raw_content']),
                'markdown_body' => $parsedMd['markdown_body'] ?? '',
                'raw_markdown' => $parsedMd['raw_content'] ?? ''
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
