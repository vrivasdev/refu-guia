<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\LocalSlmService;
use App\Services\Ai\PromptSanitizerService;
use Illuminate\Http\Request;

class SlmDiagnosticsController extends Controller
{
    protected LocalSlmService $slmService;
    protected PromptSanitizerService $sanitizer;

    public function __construct(LocalSlmService $slmService, PromptSanitizerService $sanitizer)
    {
        $this->slmService = $slmService;
        $this->sanitizer = $sanitizer;
    }

    public function health()
    {
        $health = $this->slmService->getHealth();

        return response()->json([
            'status' => $health['online'] ? 'CONNECTED' : 'STANDBY',
            'engine' => 'Ollama Local SLM',
            'model' => 'qwen2.5:1.5b',
            'health' => $health,
            'specs' => [
                'architecture' => 'Transformer Qwen 2.5 (1.54B Parameters)',
                'quantization' => 'Q4_K_M (4-bit)',
                'context_window' => '4096 / 32768 tokens',
                'privacy' => '100% Offline & On-Premise (No Cloud Telemetry)',
                'mcp_integration' => 'Active via RefuGuia MCP Server'
            ]
        ]);
    }

    public function testInference(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'system_prompt' => 'nullable|string'
        ]);

        $result = $this->slmService->generate(
            $request->prompt,
            $request->system_prompt ?? ''
        );

        return response()->json($result);
    }

    public function testPromptInjection(Request $request)
    {
        $request->validate(['malicious_text' => 'required|string']);

        $original = $request->malicious_text;
        $sanitized = $this->sanitizer->sanitize($original);

        return response()->json([
            'status' => 'PROTECTED_BY_SANITIZER',
            'original_input' => $original,
            'sanitized_output' => $sanitized,
            'is_neutralized' => ($original !== $sanitized),
            'defense_layer' => 'OWASP Top 10 LLM01 - Prompt Injection Shield'
        ]);
    }
}
