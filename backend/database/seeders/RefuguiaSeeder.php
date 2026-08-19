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
        // 1. Usuarios con roles (Credenciales para pruebas)
        $admin = User::updateOrCreate(
            ['email' => 'carmen.refugio@refuguia.org'],
            [
                'name' => 'Dra. Carmen López (Coordinadora)',
                'password' => Hash::make('Password123!'),
                'phone' => '+58 412 1234567',
                'role' => 'shelter_admin',
                'trust_score' => 1.0,
                'location_zone' => 'Caracas Norte - Refugio Central'
            ]
        );

        $rescuer = User::updateOrCreate(
            ['email' => 'carlos.rescate@refuguia.org'],
            [
                'name' => 'Carlos Mendoza (Rescatista)',
                'password' => Hash::make('Password123!'),
                'phone' => '+58 414 9876543',
                'role' => 'rescuer',
                'trust_score' => 1.0,
                'location_zone' => 'La Guaira - Zona Costera'
            ]
        );

        $citizen = User::updateOrCreate(
            ['email' => 'maria.f@gmail.com'],
            [
                'name' => 'María Fernández (Damnificada)',
                'password' => Hash::make('Password123!'),
                'phone' => '+58 424 5551234',
                'role' => 'citizen',
                'trust_score' => 1.0,
                'location_zone' => 'Catia, Caracas'
            ]
        );

        $adopter = User::updateOrCreate(
            ['email' => 'andres.m@gmail.com'],
            [
                'name' => 'Andrés Morales (Adoptante)',
                'password' => Hash::make('Password123!'),
                'phone' => '+58 416 8889999',
                'role' => 'adopter',
                'trust_score' => 1.0,
                'location_zone' => 'El Hatillo, Caracas'
            ]
        );

        // 2. Mascotas Perdidas
        $lost1 = Pet::updateOrCreate(
            ['uuid' => 'RG-2026-PERD01'],
            [
                'report_type' => 'lost',
                'name' => 'Toby',
                'species' => 'canine',
                'breed' => 'Border Collie Mestizo',
                'size' => 'medium',
                'primary_color' => 'Negro',
                'secondary_color' => 'Blanco',
                'coat_pattern' => 'Bicolor con pecho blanco',
                'distinctive_marks' => 'Mancha negra sobre el ojo izquierdo',
                'status' => 'lost',
                'photo_url' => 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80',
                'latitude' => 10.5000,
                'longitude' => -66.9167,
                'location_address' => 'Av. Sucre, Catia, Caracas',
                'rescue_date' => Carbon::now()->subDays(18),
                'user_id' => $citizen->id
            ]
        );

        // 3. Mascotas Encontradas
        $found1 = Pet::updateOrCreate(
            ['uuid' => 'RG-2026-000512'],
            [
                'report_type' => 'found',
                'name' => 'Rescatado Toby (Provisorio)',
                'species' => 'canine',
                'breed' => 'Border Collie Mestizo',
                'size' => 'medium',
                'primary_color' => 'Negro',
                'secondary_color' => 'Blanco',
                'coat_pattern' => 'Bicolor con pecho blanco',
                'distinctive_marks' => 'Cojera leve pata trasera izquierda post-sismo',
                'status' => 'in_shelter',
                'photo_url' => 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80',
                'latitude' => 10.5050,
                'longitude' => -66.9120,
                'location_address' => 'Refugio Temporal Norte - Caricuao',
                'rescue_date' => Carbon::now()->subDays(17),
                'grace_period_ends_at' => Carbon::now()->subDays(2),
                'user_id' => $rescuer->id
            ]
        );

        ClinicalRecord::firstOrCreate(
            ['pet_id' => $found1->id],
            [
                'trauma_notes' => 'Laceración suturada en almohadilla izquierda. Hidratación completada.',
                'nutritional_status' => 'Recuperado',
                'ptsd_symptoms' => ['Temblor ante ruidos fuertes'],
                'vaccines_applied' => ['Polivalente Canina', 'Antirrábica'],
                'deworming_status' => 'Completa',
                'veterinarian_name' => 'Dra. Elena Ramos',
                'audit_hash' => hash('sha256', 'RG-2026-000512-record1')
            ]
        );

        $found2 = Pet::updateOrCreate(
            ['uuid' => 'RG-2026-000588'],
            [
                'report_type' => 'found',
                'name' => 'Milo (Gatito rescatado)',
                'species' => 'feline',
                'breed' => 'Común Europeo',
                'size' => 'small',
                'primary_color' => 'Atigrado',
                'secondary_color' => 'Gris',
                'coat_pattern' => 'Rayado clásico',
                'distinctive_marks' => 'Ojos verdes, muy cariñoso',
                'status' => 'adoptable',
                'photo_url' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80',
                'latitude' => 10.4880,
                'longitude' => -66.8790,
                'location_address' => 'Refugio San Bernardino, Caracas',
                'rescue_date' => Carbon::now()->subDays(25),
                'grace_period_ends_at' => Carbon::now()->subDays(10),
                'user_id' => $admin->id
            ]
        );

        ClinicalRecord::firstOrCreate(
            ['pet_id' => $found2->id],
            [
                'trauma_notes' => 'Sin lesiones físicas. Excelente estado nutricional.',
                'nutritional_status' => 'Óptimo',
                'ptsd_symptoms' => [],
                'vaccines_applied' => ['Triple Felina'],
                'deworming_status' => 'Al día',
                'veterinarian_name' => 'Dra. Elena Ramos',
                'audit_hash' => hash('sha256', 'RG-2026-000588-record1')
            ]
        );

        // 4. Match
        MatchLog::firstOrCreate(
            ['lost_pet_id' => $lost1->id, 'found_pet_id' => $found1->id],
            [
                'similarity_score' => 91.5,
                'species_match' => true,
                'visual_score' => 95.0,
                'nlp_semantic_score' => 90.0,
                'geo_distance_km' => 1.8,
                'status' => 'alert_sent',
                'human_feedback_notes' => 'Alerta automática emitida a María Fernández. Pendiente confirmación presencial.'
            ]
        );
    }
}
