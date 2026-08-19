<?php

namespace App\Services\Mcp\Skills;

use App\Models\Pet;
use App\Models\AdoptionApplication;

class AdoptionCompatibilitySkill
{
    public function getDescription(): string
    {
        return "Sistema experto que evalúa la idoneidad y compatibilidad entre el perfil del adoptante y las necesidades médicas/espaciales de la mascota rescatada, aplicando bloqueos automáticos (Hard Stops).";
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'pet_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la mascota.'
                ],
                'monthly_income_usd' => [
                    'type' => 'number',
                    'description' => 'Ingreso mensual disponible del adoptante en USD.'
                ],
                'housing_type' => [
                    'type' => 'string',
                    'description' => 'Tipo de vivienda (apartment_small, apartment_large, house_with_patio, house_closed_patio).'
                ],
                'has_closed_patio' => [
                    'type' => 'boolean',
                    'description' => 'Si la casa tiene patio cerrado con muros/valla.'
                ],
                'has_other_pets' => [
                    'type' => 'boolean',
                    'description' => 'Presencia de otros animales en el hogar.'
                ]
            ],
            'required' => ['pet_id', 'monthly_income_usd', 'housing_type']
        ];
    }

    public function execute(array $params): array
    {
        $pet = Pet::with('clinicalRecords')->find($params['pet_id']);
        if (!$pet) {
            throw new \Exception("Mascota con ID {$params['pet_id']} no encontrada.");
        }

        $income = floatval($params['monthly_income_usd'] ?? 0);
        $housing = $params['housing_type'] ?? 'apartment_small';
        $hasClosedPatio = (bool)($params['has_closed_patio'] ?? false);
        $hasOtherPets = (bool)($params['has_other_pets'] ?? false);

        // Estimar costo médico mensual del animal según sus registros
        $estimatedMedicalCost = 25.0; // Base alimentación y vacunas
        foreach ($pet->clinicalRecords as $record) {
            if ($record->chronic_medication) {
                $estimatedMedicalCost += 50.0;
            }
        }

        $score = 100;
        $reasons = [];
        $hardStop = false;
        $decision = 'APPROVED';

        // REGLA 1: HARD STOP - Capacidad financiera
        if ($income < $estimatedMedicalCost * 1.5) {
            $hardStop = true;
            $decision = 'REJECTED_HARD_STOP';
            $score = 20;
            $reasons[] = "Bloqueo por capacidad financiera insuficiente: el animal requiere cuidados y medicación por aprox \${$estimatedMedicalCost}/mes, lo cual supera el umbral seguro para el presupuesto declarado de \${$income}.";
        }

        // REGLA 2: Espacio físico para animales grandes o con trauma
        if ($pet->size === 'large' && !$hasClosedPatio && ($housing === 'apartment_small' || $housing === 'apartment_large')) {
            $score -= 35;
            $reasons[] = "Mascota de porte grande en apartamento sin patio cerrado: requiere evaluación adicional de paseos diarios.";
        }

        // REGLA 3: Interacción con otras mascotas
        if ($hasOtherPets && $pet->size === 'large') {
            $score -= 10;
            $reasons[] = "Presencia de otros animales requiere protocolo de adaptación gradual post-trauma.";
        }

        $score = max(0, min(100, $score));

        if (!$hardStop) {
            if ($score >= 80) {
                $decision = 'APPROVED';
                $reasons[] = "Perfil altamente compatible con las necesidades clínicas y emocionales de {$pet->name}.";
            } else {
                $decision = 'REVIEW_REQUIRED';
                $reasons[] = "Compatibilidad moderada: se sugiere entrevista previa con el comité del refugio.";
            }
        }

        return [
            'pet_id' => $pet->id,
            'pet_name' => $pet->name,
            'estimated_monthly_care_cost_usd' => $estimatedMedicalCost,
            'suitability_score' => $score,
            'ai_decision' => $decision,
            'hard_stop_triggered' => $hardStop,
            'rationale' => implode(" ", $reasons)
        ];
    }
}
