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
        $result = $this->mcpServer->executeTool('skill_buscar_similitud_vectorial', [
            'target_pet_id' => $pet->id
        ], 'Agente_Emparejador');

        return response()->json($result);
    }

    public function confirmMatch(Request $request, $matchId)
    {
        $match = MatchLog::findOrFail($matchId);
        $match->status = 'confirmed_by_human';
        $match->human_feedback_notes = $request->notes ?? 'Confirmado visualmente por el tutor y rescatista.';
        $match->save();

        // Actualizar estado de mascotas
        Pet::whereIn('id', [$match->lost_pet_id, $match->found_pet_id])->update(['status' => 'reunified']);

        return response()->json([
            'success' => true,
            'message' => '¡Mascota reunificada con éxito con su familia!',
            'data' => $match
        ]);
    }

    public function rejectMatch(Request $request, $matchId)
    {
        $match = MatchLog::findOrFail($matchId);
        $match->status = 'rejected_by_human';
        $match->human_feedback_notes = $request->notes ?? 'Rechazado por el usuario (rasgos no coincidentes).';
        $match->save();

        return response()->json([
            'success' => true,
            'message' => 'Match descartado. El Agente de Aprendizaje calibrará los pesos para reducir falsos positivos en esta zona.',
            'data' => $match
        ]);
    }
}
