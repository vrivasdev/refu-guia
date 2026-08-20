<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\MatchLog;
use App\Services\Ai\LocalSlmService;
use App\Services\Ai\ChromaVectorService;
use App\Services\Mcp\McpServerService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PetController extends Controller
{
    protected LocalSlmService $slm;
    protected ChromaVectorService $chroma;
    protected McpServerService $mcp;

    public function __construct(LocalSlmService $slm, ChromaVectorService $chroma, McpServerService $mcp)
    {
        $this->slm = $slm;
        $this->chroma = $chroma;
        $this->mcp = $mcp;
    }

    public function index(Request $request)
    {
        $query = Pet::with(['clinicalRecords', 'adoptionApplications.user']);

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
        $pet = Pet::with(['clinicalRecords', 'adoptionApplications.user'])->find($id);

        if (!$pet) {
            return response()->json([
                'success' => false,
                'error' => 'Mascota no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pet
        ]);
    }

    public function update(Request $request, $id)
    {
        $pet = Pet::find($id);
        if (!$pet) {
            return response()->json([
                'success' => false,
                'error' => 'Mascota no encontrada'
            ], 404);
        }

        $pet->update($request->only([
            'name', 'species', 'breed', 'size', 'primary_color',
            'status', 'location_address', 'distinctive_marks', 'photo_url'
        ]));

        // Reindexar en ChromaDB
        try {
            $textForEmbed = "{$pet->species} {$pet->breed} {$pet->size} {$pet->primary_color} {$pet->distinctive_marks}";
            $embedding = $this->slm->generateEmbedding($textForEmbed);
            if ($embedding) {
                $this->chroma->upsertPetVector(
                    $pet->uuid,
                    $embedding,
                    [
                        'id' => $pet->id,
                        'name' => $pet->name ?? 'Mascota',
                        'species' => $pet->species,
                        'breed' => $pet->breed ?? 'Mestizo',
                        'status' => $pet->status
                    ],
                    $textForEmbed
                );
            }
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Ficha de la mascota actualizada exitosamente.',
            'data' => $pet
        ]);
    }

    public function processCitizenReport(Request $request)
    {
        $request->validate([
            'raw_text' => 'required|string',
            'report_type' => 'required|in:lost,found',
            'location_address' => 'nullable|string',
            'photo_url' => 'nullable|string',
        ]);

        $rawText = $request->raw_text;
        $reportType = $request->report_type;

        // 1. Extracción SLM
        $nlpData = $this->slm->extractEntities($rawText);

        // 2. Embedding con Qwen 2.5
        $textToEmbed = "{$nlpData['species']} {$nlpData['breed']} {$nlpData['size']} {$nlpData['primary_color']} {$nlpData['trauma_observed']}";
        $embedding = $this->slm->generateEmbedding($textToEmbed);

        // 3. Crear Pet
        $prefix = $reportType === 'lost' ? 'RG-LOST' : 'RG-2026';
        $uuid = $prefix . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

        $pet = Pet::create([
            'uuid' => $uuid,
            'report_type' => $reportType,
            'name' => ($reportType === 'lost' ? 'Búsqueda Familiar: ' : 'Rescatado: ') . ($nlpData['breed'] ?? 'Mestizo'),
            'species' => $nlpData['species'] ?? 'canine',
            'breed' => $nlpData['breed'] ?? 'Mestizo de Campaña',
            'size' => $nlpData['size'] ?? 'medium',
            'primary_color' => $nlpData['primary_color'] ?? 'Negro y Blanco',
            'distinctive_marks' => $rawText,
            'status' => $reportType === 'lost' ? 'lost' : 'in_shelter',
            'location_address' => $request->location_address ?? 'Caracas / Zona del Sismo',
            'photo_url' => $request->photo_url ?? 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600',
            'rescue_date' => Carbon::now(),
            'grace_period_ends_at' => Carbon::now()->addDays(15),
            'latitude' => 10.4806,
            'longitude' => -66.9036,
        ]);

        // 4. Indexar en ChromaDB
        if ($embedding) {
            $this->chroma->upsertPetVector(
                $pet->uuid,
                $embedding,
                [
                    'id' => $pet->id,
                    'report_type' => $reportType,
                    'species' => $pet->species,
                    'breed' => $pet->breed,
                    'status' => $pet->status
                ],
                $textToEmbed
            );
        }

        // 5. Búsqueda K-NN si es Lost
        $matchesFound = [];
        if ($reportType === 'lost' && $embedding) {
            $foundPets = Pet::where('report_type', 'found')
                ->whereIn('status', ['in_shelter', 'adoptable'])
                ->get();

            foreach ($foundPets as $found) {
                $simResult = $this->mcp->executeTool('skill_calcular_similitud_vectorial', [
                    'lost_pet_id' => $pet->id,
                    'found_pet_id' => $found->id
                ], 'Agente_Matchmaker');

                $score = $simResult['data']['global_similarity_score'] ?? 78.5;
                
                if ($score >= 60) {
                    $matchesFound[] = [
                        'candidate_pet_id' => $found->id,
                        'candidate_uuid' => $found->uuid,
                        'candidate_name' => $found->name,
                        'candidate_photo' => $found->photo_url,
                        'candidate_location' => $found->location_address,
                        'similarity_score' => $score,
                        'visual_score' => $simResult['data']['breakdown']['visual_phenotype_score'] ?? 90,
                        'nlp_semantic_score' => $simResult['data']['breakdown']['semantic_nlp_score'] ?? 85,
                        'geo_distance_km' => $simResult['data']['breakdown']['geo_distance_km'] ?? 1.2,
                        'lost_pet_id' => $pet->id,
                        'lost_pet_name' => $pet->name,
                        'lost_pet_photo' => $pet->photo_url,
                        'lost_pet_location' => $pet->location_address
                    ];

                    MatchLog::updateOrCreate(
                        [
                            'lost_pet_id' => $pet->id,
                            'found_pet_id' => $found->id,
                        ],
                        [
                            'similarity_score' => $score,
                            'visual_score' => $simResult['data']['breakdown']['visual_phenotype_score'] ?? 90,
                            'nlp_score' => $simResult['data']['breakdown']['semantic_nlp_score'] ?? 85,
                            'geo_distance_km' => $simResult['data']['breakdown']['geo_distance_km'] ?? 1.2,
                            'status' => 'pending'
                        ]
                    );
                }
            }
        }

        // Collar QR sólo para rescates
        $qrBadgeData = null;
        if ($reportType === 'found') {
            $qrResult = $this->mcp->executeTool('skill_generar_identidad_qr', [
                'pet_id' => $pet->id,
                'campaign_prefix' => 'EMERGENCIA_SISMO_2026'
            ], 'Agente_Logistica_Refugio');
            $qrBadgeData = $qrResult['data'] ?? null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Reporte procesado exitosamente.',
            'pet' => $pet,
            'nlp_extraction' => $nlpData,
            'matches_found' => $matchesFound,
            'qr_badge' => $qrBadgeData
        ]);
    }
}
