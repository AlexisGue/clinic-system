<?php

namespace App\Modules\Consultations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'recorded_at',
        'weight_kg',
        'height_cm',
        'bp_systolic',
        'bp_diastolic',
        'heart_rate',
        'temperature_c',
        'spo2',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'temperature_c' => 'decimal:1',
            'bp_systolic' => 'integer',
            'bp_diastolic' => 'integer',
            'heart_rate' => 'integer',
            'spo2' => 'integer',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
