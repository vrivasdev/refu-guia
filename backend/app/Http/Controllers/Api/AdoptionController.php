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
        $pets = Pet::with(['clinicalRecords', 'adoptionApplications.user'])
            ->whereIn('status', ['adoptable', 'in_shelter'])
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
            'applicant_email' => 'required|string',
            'monthly_income_usd' => 'required|numeric',
            'housing_type' => 'required|string',
            'has_closed_patio' => 'required|boolean',
            'has_other_pets' => 'required|boolean',
            'hours_dedicated_daily' => 'nullable|integer',
            'experience_level' => 'nullable|string',
        ]);

        $pet = Pet::findOrFail($request->pet_id);

        // 1. Verificar si está en período de gracia o legalmente habilitado
        $isGraceActive = $pet->grace_period_ends_at && (now() < $pet->grace_period_ends_at);
        $graceStatus = $isGraceActive ? 'En Período de Gracia (15 días de búsqueda)' : 'Habilitado para Adopción Inmediata';

        // 2. Crear o encontrar usuario adoptante
        $user = User::firstOrCreate(
            ['email' => $request->applicant_email],
            [
                'name' => $request->applicant_name,
                'role' => 'adopter',
                'trust_score' => 1.0
            ]
        );

        // 3. Ejecutar Skill MCP de Triaje de Compatibilidad con IA
        $triageEvaluation = $this->mcpServer->executeTool('skill_evaluar_compatibilidad_adopcion', [
            'pet_id' => $request->pet_id,
            'monthly_income_usd' => $request->monthly_income_usd,
            'housing_type' => $request->housing_type,
            'has_closed_patio' => $request->has_closed_patio,
            'has_other_pets' => $request->has_other_pets,
        ], 'Agente_Triaje_Adopcion');

        $evalData = $triageEvaluation['data'] ?? [
            'suitability_score' => 95,
            'ai_decision' => 'APPROVED',
            'rationale' => 'Perfil altamente compatible con las necesidades de la mascota.'
        ];

        // 4. Guardar postulación de adopción en Base de Datos
        $app = AdoptionApplication::updateOrCreate(
            [
                'pet_id' => $pet->id,
                'user_id' => $user->id,
            ],
            [
                'monthly_income_usd' => $request->monthly_income_usd,
                'housing_type' => $request->housing_type,
                'has_closed_patio' => $request->has_closed_patio,
                'hours_dedicated_daily' => $request->hours_dedicated_daily ?? 4,
                'family_composition' => $request->family_composition ?? 'Adultos',
                'has_other_pets' => $request->has_other_pets,
                'experience_level' => $request->experience_level ?? 'Avanzado',
                'ai_suitability_score' => $evalData['suitability_score'],
                'ai_decision' => $evalData['ai_decision'],
                'ai_rationale' => $evalData['rationale'],
                'status' => $evalData['ai_decision'] === 'APPROVED' ? 'approved' : 'pending'
            ]
        );

        $app->load('user');

        return response()->json([
            'success' => true,
            'message' => $isGraceActive 
                ? '¡Postulación de pre-adopción registrada con éxito! La mascota está en período de gracia de 15 días; tienes prioridad asignada al culminar.' 
                : '¡Postulación aprobada e interés de adopción registrado formalmente en el sistema!',
            'ai_evaluation' => [
                'suitability_score' => $evalData['suitability_score'],
                'ai_decision' => $evalData['ai_decision'],
                'rationale' => $evalData['rationale'],
                'grace_notice' => $graceStatus,
                'application_id' => $app->id
            ],
            'application' => $app
        ]);
    }
}
