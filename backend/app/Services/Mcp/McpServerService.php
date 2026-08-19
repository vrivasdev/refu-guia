<?php

namespace App\Services\Mcp;

use App\Models\McpExecutionLog;
use Illuminate\Support\Facades\Log;

class McpServerService
{
    protected McpToolRegistry $toolRegistry;

    public function __construct(McpToolRegistry $toolRegistry)
    {
        $this->toolRegistry = $toolRegistry;
    }

    public function getRegisteredTools(): array
    {
        return $this->toolRegistry->getToolsManifest();
    }

    public function executeTool(string $toolName, array $arguments, string $agentName = 'Agente_SLM_Orquestador'): array
    {
        $startTime = microtime(true);
        $tool = $this->toolRegistry->getTool($toolName);

        if (!$tool) {
            $errorResponse = [
                'success' => false,
                'error' => "Herramienta MCP '{$toolName}' no encontrada en el catálogo registrado.",
            ];
            $this->logExecution($agentName, $toolName, $arguments, $errorResponse, 0, 'error');
            return $errorResponse;
        }

        try {
            $result = $tool->execute($arguments);
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);
            $response = [
                'success' => true,
                'tool' => $toolName,
                'data' => $result,
                'execution_time_ms' => $executionTimeMs
            ];
            $this->logExecution($agentName, $toolName, $arguments, $response, $executionTimeMs, 'success');
            return $response;
        } catch (\Exception $e) {
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);
            $errorResponse = [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTimeMs
            ];
            $this->logExecution($agentName, $toolName, $arguments, $errorResponse, $executionTimeMs, 'error');
            return $errorResponse;
        }
    }

    protected function logExecution(string $agentName, string $toolName, array $input, array $output, float $timeMs, string $status): void
    {
        try {
            McpExecutionLog::create([
                'agent_name' => $agentName,
                'tool_name' => $toolName,
                'input_payload' => $input,
                'output_payload' => $output,
                'execution_time_ms' => $timeMs,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::warning("No se pudo persistir el log de MCP: " . $e->getMessage());
        }
    }
}
