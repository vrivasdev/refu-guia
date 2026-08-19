<?php

namespace App\Services\Mcp\Skills;

use App\Models\Pet;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrIdentitySkill
{
    public function getDescription(): string
    {
        return "Genera un identificador único seguro (UUID) y renderiza el código QR clínico oficial para el collar físico de refugio.";
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'pet_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la mascota para sincronizar expediente digital y collar físico.'
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

        $qrDataPayload = [
            'system' => 'RefuGuia Post-Sismo',
            'uuid' => $pet->uuid,
            'pet_id' => $pet->id,
            'species' => $pet->species,
            'status' => $pet->status,
            'verification_endpoint' => url("/api/pets/verify/{$pet->uuid}"),
            'issued_at' => now()->toIso8601String()
        ];

        // Payload serializado para el QR
        $qrString = json_encode($qrDataPayload);

        return [
            'uuid' => $pet->uuid,
            'pet_name' => $pet->name ?? 'Identificador Provisorio',
            'qr_payload_string' => $qrString,
            'print_ready_badge' => [
                'header' => 'REFUGIO TEMPORAL POST-SISMO',
                'collar_code' => $pet->uuid,
                'rescue_date' => $pet->rescue_date ? $pet->rescue_date->format('d/m/Y') : now()->format('d/m/Y'),
                'qr_preview_url' => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qrString)
            ]
        ];
    }
}
