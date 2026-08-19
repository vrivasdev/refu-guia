<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\ClinicalRecord;
use App\Services\Ai\LocalSlmService;
use App\Services\Mcp\McpServerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PetController extends Controller
{
    protected LocalSlmService $slmService;
    protected McpServerService $mcpServer;

    public function __construct(LocalSlmService $slmService, McpServerService $mcpServer)
    {
        $this->slmService = $slmService;
        $this->mcpServer = $mcpServer;
    }

    public function index(Request $request)
    {
        $query = Pet::with(['clinicalRecords', 'user'])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('report_type')) {
            $query->where('report_type', $request->report_type);
        }
        if ($request->has('species')) {
            $query->where('species', $request->species);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    public function show($id)
    {
        $pet = Pet::with(['clinicalRecords', 'user', 'matchLogs.lostPet'])->where('id', $id)->orWhere('uuid', $id)->first();
        if (!$pet) {
            return response()->json(['success' => false, 'error' => 'Mascota no encontrada'], 404);
        }

        // Obtener estado de gracia
        $graceCheck = $this->mcpServer->executeTool('skill_verificar_periodo_gracia', ['pet_id' => $pet->id]);

        return response()->json([
            'success' => true,
            'data' => $pet,
            'grace_period_audit' => $graceCheck['data'] ?? null
        ]);
    }

    public function processCitizenReport(Request $request)
    {
        $request->validate([
            'raw_text' => 'required|string',
            'report_type' => 'required|in:lost,found',
            'photo_url' => 'nullable|string',
            'location_address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'user_name' => 'nullable|string',
            'user_phone' => 'nullable|string'
        ]);

        // 1. Procesar reporte mediante SLM + Skill MCP de Extracción
        $nlpProcessed = $this->slmService->processCitizenEmergencyReport(
            $request->raw_text,
            $request->report_type
        );
        $extracted = $nlpProcessed['extracted_data'];

        // 2. Generar UUID oficial
        $uuid = 'RG-2026-' . strtoupper(Str::random(6));

        // 3. Crear registro de Mascota
        $pet = Pet::create([
            'uuid' => $uuid,
            'report_type' => $request->report_type,
            'name' => $request->name ?? ($request->report_type === 'found' ? "Rescatado ({$extracted['primary_color']})" : "Mascota Extraviada"),
            'species' => $extracted['species'] ?? 'canine',
            'breed' => $request->breed ?? 'Mestizo Local',
            'size' => $extracted['size'] ?? 'medium',
            'primary_color' => $extracted['primary_color'] ?? 'Marron',
            'secondary_color' => $extracted['secondary_color'] ?? null,
            'coat_pattern' => $request->coat_pattern ?? 'Sólido',
            'distinctive_marks' => $extracted['health_state'] ?? null,
            'status' => $request->report_type === 'found' ? 'in_shelter' : 'lost',
            'photo_url' => $request->photo_url ?? 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=500&auto=format&fit=crop&q=60',
            'latitude' => $request->latitude ?? 10.4806,
            'longitude' => $request->longitude ?? -66.9036,
            'location_address' => $request->location_address ?? 'Zona de Afectación Sísmica (Caracas)',
            'rescue_date' => now(),
            'grace_period_ends_at' => now()->addDays(15),
        ]);

        // 4. Crear expediente clínico inicial si es encontrado
        if ($request->report_type === 'found') {
            ClinicalRecord::create([
                'pet_id' => $pet->id,
                'trauma_notes' => $extracted['health_state'] ?? 'Evaluación inicial en campamento',
                'nutritional_status' => 'Moderado',
                'ptsd_symptoms' => ['Desorientación', 'Hipersensibilidad sonora'],
                'vaccines_applied' => ['Antirrábica de Emergencia'],
                'deworming_status' => 'Administrada',
                'veterinarian_name' => 'Dr. Alejandro Méndez (Voluntario)',
                'audit_hash' => hash('sha256', $pet->uuid . now())
            ]);
        }

        // 5. Ejecutar Skill MCP de Búsqueda Vectorial para buscar Matches inmediatos
        $matchResults = $this->mcpServer->executeTool('skill_buscar_similitud_vectorial', [
            'target_pet_id' => $pet->id,
            'target_type' => $request->report_type
        ], 'Agente_Emparejador_Central');

        // 6. Generar QR oficial para impresión física
        $qrResult = $this->mcpServer->executeTool('skill_generar_identidad_qr', [
            'pet_id' => $pet->id
        ], 'Agente_Gestor_Identidad_QR');

        return response()->json([
            'success' => true,
            'message' => 'Reporte procesado exitosamente por la flota agéntica.',
            'pet' => $pet->load('clinicalRecords'),
            'nlp_extraction' => $nlpProcessed,
            'qr_badge' => $qrResult['data'] ?? null,
            'matches_found' => $matchResults['data']['top_matches'] ?? []
        ], 201);
    }
}
