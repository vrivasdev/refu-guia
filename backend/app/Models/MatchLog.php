<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lost_pet_id',
        'found_pet_id',
        'similarity_score',
        'species_match',
        'visual_score',
        'nlp_semantic_score',
        'geo_distance_km',
        'status', // 'pending_review', 'alert_sent', 'confirmed_by_human', 'rejected_by_human'
        'human_feedback_notes'
    ];

    protected $casts = [
        'similarity_score' => 'float',
        'visual_score' => 'float',
        'nlp_semantic_score' => 'float',
        'geo_distance_km' => 'float',
        'species_match' => 'boolean',
    ];

    public function lostPet()
    {
        return $this->belongsTo(Pet::class, 'lost_pet_id');
    }

    public function foundPet()
    {
        return $this->belongsTo(Pet::class, 'found_pet_id');
    }
}
