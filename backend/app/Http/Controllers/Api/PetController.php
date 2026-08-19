<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Services\Ai\LocalSlmService;
use App\Services\Ai\ChromaVectorService;
use App\Services\Mcp\Skills\QrIdentitySkill;
use App\Services\Mcp\Skills\VectorSimilaritySkill;
use Illuminate\Http\Request;
use Carbon\Carbon;
use RuntimeException;

class PetController extends Controller
{
    protected LocalSlmService $slmService;
    protected ChromaVectorService $chromaService;
    protected QrIdentitySkill $qrSkill;
    protected VectorSimilaritySkill $vectorSkill;

    public function __construct(
        LocalSlmService $slmService,
        ChromaVectorService $chromaService,
        QrIdentitySkill $qrSkill,
        VectorSimilaritySkill $vectorSkill
    ) {
        $this->slmService = $slmService;
        $this->chromaService = $chromaService;
        $this->qrSkill = $qrSkill;
        $this->vectorSkill = $vectorSkill;
    }

    public function index(Request $request)
    {
        $query = Pet::with('clinicalRecords');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('report_type')) {
            $query->where('report_type', $request->report_type);
        }

        $pets = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $pets->count(),
            'data' => $pets
        ]);
    }

    public function show($id)
    {
        $pet = Pet::with(['clinicalRecords', 'user'])->find($id);

        if (!$pet) {
            return response()->json(['success' => false, 'error' => 'Mascota no encontrada'], 404);
        }

        return response()->json(['success' => true, 'data' => $pet]);
    }

    public function update(Request $request, $id)
    {
        $pet = Pet::find($id);

        if (!$pet) {
            return response()->json(['success' => false, 'error' => 'Mascota no encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'species' => 'nullable|in:canine,feline,other',
            'breed' => 'nullable|string|max:100',
            'size' => 'nullable|in:small,medium,large',
            'primary_color' => 'nullable|string|max:50',
            'secondary_color' => 'nullable|string|max:50',
            'distinctive_marks' => 'nullable|string',
            'status' => 'nullable|in:lost,found,in_shelter,adoptable,reunified',
            'location_address' => 'nullable|string',
            'photo_url' => 'nullable|string'
        ]);

        $pet->update($validated);

        // Actualizar vector en ChromaDB con embedding real
        $this->chromaService->indexPetDocument(
            $pet->id,
            "{$pet->name} {$pet->species} {$pet->breed} {$pet->primary_color} {$pet->distinctive_marks}",
            [
                'pet_id' => $pet->id,
                'uuid' => $pet->uuid,
                'report_type' => $pet->report_type,
                'species' => $pet->species,
                'status' => $pet->status
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ficha de la mascota actualizada exitosamente.',
            'data' => $pet->fresh(['clinicalRecords', 'user'])
        ]);
    }

    public function processCitizenReport(Request $request)
    {
        $request->validate([
            'raw_text' => 'required|string',
            'report_type' => 'required|in:lost,found',
            'photo_url' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        $rawText = $request->raw_text;
        $reportType = $request->report_type;

        // 1. Inferencia Obligatoria con Modelo Local Ollama (Qwen 2.5:1.5B)
        try {
            $nlpData = $this->slmService->extractEntities($rawText);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => "El modelo local Ollama (qwen2.5:1.5b) debe estar ejecutándose para vectorizar y registrar el reporte. Por favor inicia Ollama con: ollama run qwen2.5:1.5b",
                'details' => $e->getMessage()
            ], 503);
        }

        // 2. Normalizar datos extraídos por el SLM
        $species = $nlpData['species'];
        $size = $nlpData['size'];
        $uuid = 'RG-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        $now = Carbon::now();

        // 3. Crear registro en BD MySQL
        $pet = Pet::create([
            'uuid' => $uuid,
            'report_type' => $reportType,
            'name' => ($reportType === 'lost') 
                ? "Búsqueda Familiar: {$nlpData['breed']}" 
                : "Rescatado: {$nlpData['breed']}",
            'species' => $species,
            'breed' => $nlpData['breed'],
            'size' => $size,
            'primary_color' => $nlpData['primary_color'],
            'secondary_color' => $nlpData['secondary_color'],
            'coat_pattern' => $nlpData['coat_pattern'],
            'distinctive_marks' => $nlpData['distinctive_marks'] . ' | Trauma: ' . $nlpData['trauma_observed'],
            'status' => ($reportType === 'lost') ? 'lost' : 'in_shelter',
            'photo_url' => $request->photo_url ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600',
            'latitude' => $request->latitude ?? 10.5000,
            'longitude' => $request->longitude ?? -66.9167,
            'location_address' => $nlpData['location_extracted'],
            'rescue_date' => $now,
            'grace_period_ends_at' => ($reportType === 'found') ? $now->copy()->addDays(15) : null,
            'user_id' => 1
        ]);

        // 4. Indexación y Vectorización Real en ChromaDB
        $this->chromaService->indexPetDocument(
            $pet->id,
            "{$pet->name} {$pet->species} {$pet->breed} {$pet->primary_color} {$pet->distinctive_marks}",
            [
                'pet_id' => $pet->id,
                'uuid' => $pet->uuid,
                'report_type' => $pet->report_type,
                'species' => $pet->species,
                'status' => $pet->status
            ]
        );

        // 5. FLUJO DIFERENCIADO POR ROL:
        // A) SI ES RESCATE (RESCATISTA/REFUGIO): Genera Collar QR físico
        $qrBadgeData = null;
        if ($reportType === 'found') {
            $qrData = $this->qrSkill->execute(['pet_id' => $pet->id]);
            $qrBadgeData = $qrData['data'] ?? null;
        }

        // B) SI ES BÚSQUEDA FAMILIAR (DAMNIFICADO): Ejecuta búsqueda vectorial contra los rescatados
        $matchesFound = [];
        if ($reportType === 'lost') {
            $similarityResult = $this->vectorSkill->execute([
                'target_pet_id' => $pet->id,
                'target_type' => 'found'
            ]);
            $matchesFound = $similarityResult['top_matches'] ?? [];
        }

        return response()->json([
            'success' => true,
            'message' => ($reportType === 'lost')
                ? 'Reporte de búsqueda familiar registrado y cotejado contra el inventario de refugios.'
                : 'Mascota rescatada registrada e ingresada al inventario del refugio.',
            'report_type' => $reportType,
            'pet' => $pet,
            'nlp_extraction' => $nlpData,
            'qr_badge' => $qrBadgeData,
            'matches_found' => $matchesFound
        ], 201);
    }
}
