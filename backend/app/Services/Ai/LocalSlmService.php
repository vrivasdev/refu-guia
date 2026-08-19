<?php

namespace App\Services\Ai;

use App\Services\Mcp\McpServerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalSlmService
{
    protected string $ollamaHost;
    protected string $model;
    protected McpServerService $mcpServer;
    protected PromptSanitizerService $sanitizer;

    public function __construct(McpServerService $mcpServer, PromptSanitizerService $sanitizer)
    {
        $this->ollamaHost = env('OLLAMA_HOST', 'http://host.docker.internal:11434');
        $this->model = env('OLLAMA_MODEL', 'qwen2.5:1.5b');
        $this->mcpServer = $mcpServer;
        $this->sanitizer = $sanitizer;
    }

    public function checkHealth(): array
    {
        try {
            $response = Http::timeout(2)->get("{$this->ollamaHost}/api/tags");
            if ($response->successful()) {
                $models = $response->json('models', []);
                return [
                    'online' => true,
                    'host' => $this->ollamaHost,
                    'configured_model' => $this->model,
                    'available_models' => array_column($models, 'name')
                ];
            }
        } catch (\Exception $e) {
            // Offline
        }

        return [
            'online' => false,
            'host' => $this->ollamaHost,
            'configured_model' => $this->model,
            'message' => 'Ollama local no detectado en el puerto 11434. El sistema opera con motor de inferencia y Skills MCP deterministas.'
        ];
    }

    public function processCitizenEmergencyReport(string $rawText, string $reportType = 'found'): array
    {
        $sanitizedText = $this->sanitizer->sanitize($rawText);

        // 1. Invocar Skill MCP de Extracción NLP
        $nlpResult = $this->mcpServer->executeTool('skill_extraer_entidades_nlp', [
            'text' => $sanitizedText,
            'report_type' => $reportType
        ], 'Agente_NLP_Ciudadano');

        return [
            'sanitized_input' => $sanitizedText,
            'mcp_execution' => $nlpResult,
            'extracted_data' => $nlpResult['data'] ?? []
        ];
    }
}
