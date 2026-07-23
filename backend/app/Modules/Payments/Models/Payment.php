<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Consultations\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Payment extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $fillable = [
        'consultation_id',
        'appointment_id',
        'amount',
        'currency',
        'payment_method_id',
        'paid_at',
        'reference',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
