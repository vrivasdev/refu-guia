<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id',
        'trauma_notes',
        'nutritional_status',
        'ptsd_symptoms',
        'vaccines_applied',
        'deworming_status',
        'chronic_medication',
        'critical_drug_administered',
        'qr_scanned_before_medication',
        'veterinarian_name',
        'audit_hash'
    ];

    protected $casts = [
        'qr_scanned_before_medication' => 'boolean',
        'vaccines_applied' => 'array',
        'ptsd_symptoms' => 'array',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
