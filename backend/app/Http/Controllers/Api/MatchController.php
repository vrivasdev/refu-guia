<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchLog;
use App\Models\Pet;
use App\Services\Mcp\McpServerService;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    protected McpServerService $mcpServer;

    public function __construct(McpServerService $mcpServer)
    {
        $this->mcpServer = $mcpServer;
    }

    public function index()
    {
        $matches = MatchLog::with(['lostPet', 'foundPet'])->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    public function runMatchForPet($petId)
    {
        $pet = Pet::findOrFail($petId);
        
        // Corregido: invocar el nombre exacto de la skill en el registro MCP
        $result = $this->mcpServer->executeTool('skill_calcular_similitud_vectorial', [
            'lost_pet_id' => $pet->id,
            'found_pet_id' => 2
        ], 'Agente_Emparejador');

        return response()->json($result);
    }

    public function confirmMatch(Request $request, $matchId)
    {
        $match = MatchLog::findOrFail($matchId);
        $match->status = 'confirmed_by_human';
        $match->human_feedback_notes = $request->notes ?? 'Confirmado formalmente por tutor legal y rescatista.';
        $match->save();

        // Actualizar estado de ambas mascotas a reunificadas
        Pet::whereIn('id', [$match->lost_pet_id, $match->found_pet_id])->update(['status' => 'reunified']);

        return response()->json([
            'success' => true,
            'message' => '¡Mascota reunificada con éxito con su familia tutora!',
            'data' => $match
        ]);
    }

    public function rejectMatch(Request $request, $matchId)
    {
        $match = MatchLog::findOrFail($matchId);
        $match->status = 'rejected_by_human';
        $match->human_feedback_notes = $request->notes ?? 'Descartado por rescatista/tutor tras inspección visual.';
        $match->save();

        return response()->json([
            'success' => true,
            'message' => 'Match descartado. Registro de auditoría actualizado.',
            'data' => $match
        ]);
    }
}
