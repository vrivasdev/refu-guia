<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\ClinicalRecord;
use Illuminate\Http\Request;

class ClinicalRecordController extends Controller
{
    public function store(Request $request, $petId)
    {
        $pet = Pet::where('id', $petId)->orWhere('uuid', $petId)->firstOrFail();

        $request->validate([
            'qr_scanned' => 'required|boolean',
            'critical_drug' => 'nullable|string',
            'trauma_notes' => 'nullable|string',
            'veterinarian_name' => 'required|string',
        ]);

        // Regla de Negocio Crítica: Bloqueo si no se escanea el QR
        if (!empty($request->critical_drug) && !$request->qr_scanned) {
            return response()->json([
                'success' => false,
                'error' => 'REGLA DE SEGURIDAD VIOLADA: Está prohibido administrar fármacos críticos sin el escaneo previo y confirmación del código QR físico en el collar del animal.'
            ], 422);
        }

        $record = ClinicalRecord::create([
            'pet_id' => $pet->id,
            'trauma_notes' => $request->trauma_notes ?? 'Revisión periódica de refugio',
            'nutritional_status' => $request->nutritional_status ?? 'Estable',
            'ptsd_symptoms' => $request->ptsd_symptoms ?? [],
            'vaccines_applied' => $request->vaccines_applied ?? [],
            'deworming_status' => $request->deworming_status ?? 'Al día',
            'chronic_medication' => $request->chronic_medication ?? null,
            'critical_drug_administered' => $request->critical_drug ?? null,
            'qr_scanned_before_medication' => (bool)$request->qr_scanned,
            'veterinarian_name' => $request->veterinarian_name,
            'audit_hash' => hash('sha256', $pet->uuid . now() . $request->veterinarian_name)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expediente clínico actualizado con trazabilidad inmutable.',
            'data' => $record
        ], 201);
    }
}
