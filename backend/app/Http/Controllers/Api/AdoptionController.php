<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\User;
use App\Models\AdoptionApplication;
use App\Services\Mcp\McpServerService;
use Illuminate\Http\Request;

class AdoptionController extends Controller
{
    protected McpServerService $mcpServer;

    public function __construct(McpServerService $mcpServer)
    {
        $this->mcpServer = $mcpServer;
    }

    public function getAdoptablePets()
    {
        $pets = Pet::with('clinicalRecords')
            ->where('status', 'adoptable')
            ->orWhere(function ($q) {
                $q->where('status', 'in_shelter')
                  ->where('grace_period_ends_at', '<=', now());
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pets
        ]);
    }

    public function evaluateApplication(Request $request)
    {
        $request->validate([
            'pet_id' => 'required|exists:pets,id',
            'applicant_name' => 'required|string',
            'applicant_email' => 'required|email',
            'monthly_income_usd' => 'required|numeric',
            'housing_type' => 'required|string',
            'has_closed_patio' => 'required|boolean',
            'has_other_pets' => 'required|boolean',
            'hours_dedicated_daily' => 'required|integer',
            'experience_level' => 'required|string',
        ]);

        // 1. REGLA DURA: Verificar período de gracia
        $graceCheck = $this->mcpServer->executeTool('skill_verificar_periodo_gracia', [
            'pet_id' => $request->pet_id
        ], 'Agente_Triaje_Adopcion');

        if (!$graceCheck['data']['is_eligible_for_adoption']) {
            return response()->json([
                'success' => false,
                'error' => "BLOQUEO LEGAL DE ADOPCIÓN: {$graceCheck['data']['legal_status_label']}",
                'grace_audit' => $graceCheck['data']
            ], 422);
        }

        // 2. Crear o encontrar usuario adoptante
        $user = User::firstOrCreate(
            ['email' => $request->applicant_email],
            [
                'name' => $request->applicant_name,
                'role' => 'adopter',
                'trust_score' => 1.0
            ]
        );

        // 3. Ejecutar Skill MCP de Triaje de Adopción
        $triageEvaluation = $this->mcpServer->executeTool('skill_evaluar_compatibilidad_adopcion', [
            'pet_id' => $request->pet_id,
            'monthly_income_usd' => $request->monthly_income_usd,
            'housing_type' => $request->housing_type,
            'has_closed_patio' => $request->has_closed_patio,
            'has_other_pets' => $request->has_other_pets,
        ], 'Agente_Triaje_Adopcion');

        $evalData = $triageEvaluation['data'];

        // 4. Guardar solicitud
        $app = AdoptionApplication::create([
            'pet_id' => $request->pet_id,
            'user_id' => $user->id,
            'monthly_income_usd' => $request->monthly_income_usd,
            'housing_type' => $request->housing_type,
            'has_closed_patio' => $request->has_closed_patio,
            'hours_dedicated_daily' => $request->hours_dedicated_daily,
            'family_composition' => $request->family_composition ?? 'Adultos',
            'has_other_pets' => $request->has_other_pets,
            'experience_level' => $request->experience_level,
            'ai_suitability_score' => $evalData['suitability_score'],
            'ai_decision' => $evalData['ai_decision'],
            'ai_rationale' => $evalData['rationale'],
            'status' => $evalData['ai_decision'] === 'APPROVED' ? 'approved' : ($evalData['ai_decision'] === 'REJECTED_HARD_STOP' ? 'rejected' : 'pending')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Postulación evaluada por el Agente de Triaje de Adopción.',
            'application' => $app->load(['pet', 'user']),
            'ai_evaluation' => $evalData
        ], 201);
    }
}
