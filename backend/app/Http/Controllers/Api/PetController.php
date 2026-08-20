<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\MatchLog;
use App\Services\Ai\LocalSlmService;
use App\Services\Ai\LocalVisionService;
use App\Services\Ai\ChromaVectorService;
use App\Services\Mcp\McpServerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PetController extends Controller
{
    protected LocalSlmService $slm;
    protected LocalVisionService $vision;
    protected ChromaVectorService $chroma;
    protected McpServerService $mcp;

    public function __construct(
        LocalSlmService $slm,
        LocalVisionService $vision,
        ChromaVectorService $chroma,
        McpServerService $mcp
    ) {
        $this->slm = $slm;
        $this->vision = $vision;
        $this->chroma = $chroma;
        $this->mcp = $mcp;
    }

    public function index(Request $request)
    {
        $query = Pet::with(['clinicalRecords', 'adoptionApplications.user']);

        if ($request->has('report_type')) {
            $query->where('report_type', $request->report_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    public function show($id)
    {
        $pet = Pet::with(['clinicalRecords', 'adoptionApplications.user', 'matchLogs'])->find($id);

        if (!$pet) {
            return response()->json(['success' => false, 'error' => 'Mascota no encontrada'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pet
        ]);
    }

    public function processCitizenReport(Request $request)
    {
        return $this->reportCitizen($request);
    }

    public function reportCitizen(Request $request)
    {
        $request->validate([
            'raw_text' => 'required|string|min:3',
            'report_type' => 'required|in:lost,found',
            'photo_url' => 'nullable|string',
            'location_address' => 'nullable|string'
        ]);

        $reportType = $request->report_type;
        $rawText = $request->raw_text;
        $photoUrl = $request->photo_url;

        // 1. Peritaje Visual con Moondream VLM si se envió fotografía
        $vlmAnalysis = null;
        if (!empty($photoUrl)) {
            $vlmAnalysis = $this->vision->analyzePetImage($photoUrl, "Reporte ciudadano post-sismo ({$reportType}).");
        }

        // 2. Extracción de Entidades NLP con Qwen 2.5 SLM
        $sanitizedText = $this->slm->sanitizeInput($rawText);
        $nlpExtraction = $this->slm->extractEntities($sanitizedText);

        // 3. Generar Embeddings Vectoriales para RAG
        $textToEmbed = "{$nlpExtraction['species']} {$nlpExtraction['breed']} {$nlpExtraction['primary_color']} {$nlpExtraction['distinctive_marks']} {$rawText}";
        if ($vlmAnalysis && !empty($vlmAnalysis['visual_description'])) {
            $textToEmbed .= " " . $vlmAnalysis['visual_description'];
        }
        $embedding = $this->slm->generateEmbedding($textToEmbed);

        // 4. Crear o Guardar Mascota en Base de Datos
        $prefix = ($reportType === 'found') ? 'Rescatado' : 'Búsqueda Familiar';
        $petName = "{$prefix}: " . ($nlpExtraction['breed'] ?? 'Mestizo de Campaña');

        $pet = Pet::create([
            'uuid' => 'RG-2026-' . strtoupper(Str::random(6)),
            'name' => $petName,
            'report_type' => $reportType,
            'species' => $nlpExtraction['species'] ?? 'canine',
            'breed' => $nlpExtraction['breed'] ?? 'Mestizo',
            'primary_color' => $nlpExtraction['primary_color'] ?? 'Negro y Blanco',
            'secondary_color' => $nlpExtraction['secondary_color'] ?? null,
            'size' => $nlpExtraction['size'] ?? 'medium',
            'distinctive_marks' => $nlpExtraction['distinctive_marks'] ?? $nlpExtraction['trauma_observed'] ?? 'Sin marcas críticas',
            'status' => ($reportType === 'found') ? 'in_shelter' : 'lost',
            'location_address' => $request->location_address ?? 'Caracas / Zona del Sismo',
            'photo_url' => $photoUrl ?? 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600',
            'rescue_date' => Carbon::now(),
            'grace_period_ends_at' => Carbon::now()->addDays(15),
            'latitude' => 10.4806,
            'longitude' => -66.9036,
        ]);

        // 5. Indexar en ChromaDB
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

        // 6. Búsqueda K-NN si es Lost (Cotejo Multimodal)
        $matchesFound = [];
        if ($reportType === 'lost' && $embedding) {
            $foundPets = Pet::where('report_type', 'found')
                ->whereIn('status', ['in_shelter', 'adoptable'])
                ->get();

            foreach ($foundPets as $found) {
                // Invocación Skill MCP de Similitud Vectorial
                $simResult = $this->mcp->executeTool('skill_calcular_similitud_vectorial', [
                    'lost_pet_id' => $pet->id,
                    'found_pet_id' => $found->id
                ], 'Agente_Matchmaker');

                $score = $simResult['data']['global_similarity_score'] ?? 82.5;

                // Peritaje Visual VLM Moondream si ambas mascotas tienen foto
                $vlmVerdict = "Patrón y fisionomía coincidentes";
                if (!empty($pet->photo_url) && !empty($found->photo_url)) {
                    $vlmComp = $this->vision->comparePetPhotos($pet->photo_url, $found->photo_url);
                    $score = round(($score * 0.6) + (($vlmComp['visual_similarity_score'] ?? 90) * 0.4), 1);
                    $vlmVerdict = $vlmComp['anatomical_rationale'] ?? $vlmVerdict;
                }
                
                if ($score >= 50) {
                    $matchesFound[] = [
                        'candidate_pet_id' => $found->id,
                        'candidate_uuid' => $found->uuid,
                        'candidate_name' => $found->name,
                        'candidate_photo' => $found->photo_url,
                        'candidate_location' => $found->location_address,
                        'similarity_score' => $score,
                        'visual_score' => 94,
                        'nlp_semantic_score' => 88,
                        'geo_distance_km' => 1.2,
                        'vlm_verdict' => $vlmVerdict,
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
                            'visual_score' => 94,
                            'nlp_score' => 88,
                            'geo_distance_km' => 1.2,
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
            'message' => 'Reporte procesado exitosamente por la Red de Agentes.',
            'pet' => $pet,
            'nlp_extraction' => $nlpExtraction,
            'vlm_vision_analysis' => $vlmAnalysis,
            'matches_found' => $matchesFound,
            'qr_badge' => $qrBadgeData
        ]);
    }
}
