<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pet;

class CleanPetTitlesSeeder extends Seeder
{
    public function run(): void
    {
        $pets = Pet::all();
        foreach ($pets as $p) {
            $changed = false;
            if ($p->name === 'string' || empty($p->name) || $p->name === 'not specified') {
                $p->name = ($p->species === 'feline') 
                    ? "Felino Rescatado ({$p->uuid})" 
                    : "Canino Rescatado ({$p->uuid})";
                $changed = true;
            }
            if ($p->breed === 'string' || empty($p->breed) || $p->breed === 'not specified') {
                $p->breed = 'Mestizo de Campaña';
                $changed = true;
            }
            if ($p->primary_color === 'string' || empty($p->primary_color) || $p->primary_color === 'black') {
                $p->primary_color = 'Negro y Blanco';
                $changed = true;
            }
            if ($changed) {
                $p->save();
            }
        }
    }
}
