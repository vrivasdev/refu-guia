<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdoptionApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id',
        'user_id',
        'monthly_income_usd',
        'housing_type',
        'has_closed_patio',
        'hours_dedicated_daily',
        'family_composition',
        'has_other_pets',
        'experience_level',
        'ai_suitability_score',
        'ai_decision', // 'APPROVED', 'REVIEW_REQUIRED', 'REJECTED_HARD_STOP'
        'ai_rationale',
        'status' // 'pending', 'approved', 'rejected'
    ];

    protected $casts = [
        'has_closed_patio' => 'boolean',
        'has_other_pets' => 'boolean',
        'monthly_income_usd' => 'float',
        'ai_suitability_score' => 'integer',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
