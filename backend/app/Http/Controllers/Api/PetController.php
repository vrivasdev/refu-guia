<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Services\Ai\LocalSlmService;
use App\Services\Ai\ChromaVectorService;
use App\Services\Mcp\Skills\QrIdentitySkill;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PetController extends Controller
{
    protected LocalSlmService $slmService;
    protected ChromaVectorService $chromaService;
    protected QrIdentitySkill $qrSkill;

    public function __construct(
        LocalSlmService $slmService,
        ChromaVectorService $chromaService,
        QrIdentitySkill $qrSkill
    ) {
        $this->slmService = $slmService;
        $this->chromaService = $chromaService;
        $this->qrSkill = $qrSkill;
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

        // Actualizar vector en ChromaDB
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

        // 1. Extraer entidades con Qwen 2.5:1.5b
        $nlpData = $this->slmService->extractEntities($rawText);

        // 2. Normalizar enumeraciones para la BD
        $species = in_array(strtolower($nlpData['species'] ?? ''), ['canine', 'feline', 'other']) 
            ? strtolower($nlpData['species']) 
            : 'canine';

        $size = in_array(strtolower($nlpData['size'] ?? ''), ['small', 'medium', 'large']) 
            ? strtolower($nlpData['size']) 
            : 'medium';

        // 3. Generar UUID único de emergencia
        $uuid = 'RG-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        $now = Carbon::now();

        // 4. Crear registro en BD
        $pet = Pet::create([
            'uuid' => $uuid,
            'report_type' => $reportType,
            'name' => (!empty($nlpData['breed']) && $nlpData['breed'] !== 'string') 
                ? "{$nlpData['breed']} ({$uuid})" 
                : "Mascota Rescatada ({$uuid})",
            'species' => $species,
            'breed' => (!empty($nlpData['breed']) && $nlpData['breed'] !== 'string') ? $nlpData['breed'] : 'Mestizo de Campaña',
            'size' => $size,
            'primary_color' => (!empty($nlpData['primary_color']) && $nlpData['primary_color'] !== 'string') ? $nlpData['primary_color'] : 'Negro y Blanco',
            'secondary_color' => (!empty($nlpData['secondary_color']) && $nlpData['secondary_color'] !== 'string') ? $nlpData['secondary_color'] : 'Blanco',
            'coat_pattern' => (!empty($nlpData['coat_pattern']) && $nlpData['coat_pattern'] !== 'string') ? $nlpData['coat_pattern'] : 'Bicolor',
            'distinctive_marks' => ($nlpData['distinctive_marks'] ?? 'Mascota rescatada') . ' | Trauma: ' . ($nlpData['trauma_observed'] ?? 'Ninguno'),
            'status' => ($reportType === 'lost') ? 'lost' : 'in_shelter',
            'photo_url' => $request->photo_url ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600',
            'latitude' => $request->latitude ?? 10.5000,
            'longitude' => $request->longitude ?? -66.9167,
            'location_address' => (!empty($nlpData['location_extracted']) && $nlpData['location_extracted'] !== 'string') ? $nlpData['location_extracted'] : 'Caracas / Zona del Sismo',
            'rescue_date' => $now,
            'grace_period_ends_at' => ($reportType === 'found') ? $now->copy()->addDays(15) : null,
            'user_id' => 1
        ]);

        // 5. Generar Credencial QR
        $qrData = $this->qrSkill->execute(['pet_id' => $pet->id]);

        // 6. Indexar en ChromaDB
        $this->chromaService->indexPetDocument(
            $pet->id,
            "{$pet->name} {$pet->species} {$pet->breed} {$pet->primary_color} {$pet->distinctive_marks}",
            [
                'pet_id' => $pet->id,
                'uuid' => $pet->uuid,
                'report_type' => $pet->report_type,
                'species' => $pet->species
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Reporte procesado exitosamente por el Agente NLP y registrado en RefuGuía.',
            'pet' => $pet,
            'nlp_extraction' => $nlpData,
            'qr_badge' => $qrData['data'] ?? []
        ], 201);
    }
}
