<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McpExecutionLog;
use App\Services\Mcp\McpServerService;
use Illuminate\Http\Request;

class McpController extends Controller
{
    protected McpServerService $mcpServer;

    public function __construct(McpServerService $mcpServer)
    {
        $this->mcpServer = $mcpServer;
    }

    public function getTools()
    {
        return response()->json([
            'protocol' => 'Model Context Protocol (MCP)',
            'version' => '2026.1',
            'server' => 'RefuGuia-MCP-Core',
            'tools' => $this->mcpServer->getRegisteredTools()
        ]);
    }

    public function invokeTool(Request $request)
    {
        $request->validate([
            'tool_name' => 'required|string',
            'arguments' => 'required|array',
            'agent_name' => 'nullable|string'
        ]);

        $result = $this->mcpServer->executeTool(
            $request->tool_name,
            $request->arguments,
            $request->agent_name ?? 'Agente_Inspector_MCP'
        );

        return response()->json($result);
    }

    public function getLogs()
    {
        return response()->json([
            'success' => true,
            'data' => McpExecutionLog::latest()->take(50)->get()
        ]);
    }
}
