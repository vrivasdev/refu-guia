<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'report_type', // 'lost', 'found'
        'name',
        'species',     // 'canine', 'feline', 'other'
        'breed',
        'size',        // 'small', 'medium', 'large'
        'primary_color',
        'secondary_color',
        'coat_pattern',
        'distinctive_marks',
        'status',      // 'lost', 'in_shelter', 'reunified', 'adoptable', 'adopted'
        'photo_url',
        'latitude',
        'longitude',
        'location_address',
        'rescue_date',
        'grace_period_ends_at',
        'user_id'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rescue_date' => 'date',
        'grace_period_ends_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinicalRecords()
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function matchLogs()
    {
        return $this->hasMany(MatchLog::class, 'found_pet_id');
    }
}
