<?php

namespace App\Modules\Patients\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'name',
        'relationship',
        'phone',
        'email',
        'is_emergency',
    ];

    protected function casts(): array
    {
        return [
            'is_emergency' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
