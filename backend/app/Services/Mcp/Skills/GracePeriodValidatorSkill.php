<?php

namespace App\Services\Mcp\Skills;

use App\Models\Pet;
use Carbon\Carbon;

class GracePeriodValidatorSkill
{
    public function getDescription(): string
    {
        return "Aplica la regla de negocio inamovible de 15 días continuos de búsqueda pública antes de habilitar la adopción de una mascota rescatada post-sismo.";
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'pet_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la mascota a auditar legalmente.'
                ]
            ],
            'required' => ['pet_id']
        ];
    }

    public function execute(array $params): array
    {
        $pet = Pet::find($params['pet_id']);
        if (!$pet) {
            throw new \Exception("Mascota con ID {$params['pet_id']} no encontrada.");
        }

        $rescueDate = $pet->rescue_date ? Carbon::parse($pet->rescue_date) : Carbon::parse($pet->created_at);
        $daysSinceRescue = $rescueDate->diffInDays(Carbon::now());
        $minRequiredDays = 15;
        $daysRemaining = max(0, $minRequiredDays - $daysSinceRescue);
        $isEligibleForAdoption = ($daysSinceRescue >= $minRequiredDays);

        return [
            'pet_id' => $pet->id,
            'uuid' => $pet->uuid,
            'rescue_date' => $rescueDate->toDateString(),
            'days_in_shelter' => $daysSinceRescue,
            'required_grace_period_days' => $minRequiredDays,
            'days_remaining_for_grace' => $daysRemaining,
            'is_eligible_for_adoption' => $isEligibleForAdoption,
            'legal_status_label' => $isEligibleForAdoption 
                ? 'Período legal de búsqueda cumplido. Habilitado para Adopción Responsable.' 
                : "En búsqueda activa de dueños originales. Faltan {$daysRemaining} días de gracia."
        ];
    }
}
