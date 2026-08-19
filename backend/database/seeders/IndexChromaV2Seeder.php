<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pet;
use App\Services\Ai\ChromaVectorService;

class IndexChromaV2Seeder extends Seeder
{
    public function run(): void
    {
        $chroma = new ChromaVectorService();
        $pets = Pet::all();
        foreach ($pets as $pet) {
            $chroma->indexPetDocument(
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
        }
    }
}
