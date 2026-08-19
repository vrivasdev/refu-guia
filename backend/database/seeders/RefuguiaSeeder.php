<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pet;
use App\Models\ClinicalRecord;
use App\Models\MatchLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class RefuguiaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios con roles
        $users = [
            [
                'email' => 'carmen.refugio@refuguia.org',
                'name' => 'Dra. Carmen López (Coordinadora)',
                'role' => 'shelter_admin',
                'location_zone' => 'Caracas Norte - Refugio Central'
            ],
            [
                'email' => 'carmen.lopez@refuguia.org',
                'name' => 'Dra. Carmen López (Coordinadora)',
                'role' => 'shelter_admin',
                'location_zone' => 'Caracas Norte - Refugio Central'
            ],
            [
                'email' => 'carlos.rescate@refuguia.org',
                'name' => 'Carlos Mendoza (Rescatista)',
                'role' => 'rescuer',
                'location_zone' => 'La Guaira - Zona Costera'
            ],
            [
                'email' => 'carlos.mendoza@refuguia.org',
                'name' => 'Carlos Mendoza (Rescatista)',
                'role' => 'rescuer',
                'location_zone' => 'La Guaira - Zona Costera'
            ],
            [
                'email' => 'maria.f@gmail.com',
                'name' => 'María Fernández (Damnificada)',
                'role' => 'citizen',
                'location_zone' => 'Catia, Caracas'
            ],
            [
                'email' => 'maria.fernandez@gmail.com',
                'name' => 'María Fernández (Damnificada)',
                'role' => 'citizen',
                'location_zone' => 'Catia, Caracas'
            ],
            [
                'email' => 'andres.m@gmail.com',
                'name' => 'Andrés Morales (Adoptante)',
                'role' => 'adopter',
                'location_zone' => 'El Hatillo, Caracas'
            ],
            [
                'email' => 'andres.morales@gmail.com',
                'name' => 'Andrés Morales (Adoptante)',
                'role' => 'adopter',
                'location_zone' => 'El Hatillo, Caracas'
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('Password123!'),
                    'phone' => '+58 412 1234567',
                    'role' => $u['role'],
                    'trust_score' => 1.0,
                    'location_zone' => $u['location_zone']
                ]
            );
        }
    }
}
