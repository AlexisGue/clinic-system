<?php

/**
 * Bootstrap generator for clinic backend PHP modules.
 * Run: python tools/generate_clinic_backend.py
 */
from pathlib import Path

BASE = Path(r"C:\xampp\htdocs\inventory-system\backend")

def w(rel: str, content: str):
    path = BASE / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content.strip() + "\n", encoding="utf-8")
    print("wrote", rel)


# ---------- Models ----------
w("app/Modules/Catalogs/Models/Specialty.php", r'''
<?php

namespace App\Modules\Catalogs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Specialty extends Model implements AuditableContract
{
    use Auditable, SoftDeletes;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
''')

w("app/Modules/Catalogs/Models/Medicine.php", r'''
<?php

namespace App\Modules\Catalogs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Medicine extends Model implements AuditableContract
{
    use Auditable, SoftDeletes;

    protected $fillable = ['name', 'presentation', 'concentration', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
''')

w("app/Modules/Payments/Models/PaymentMethod.php", r'''
<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
''')

w("app/Modules/Patients/Models/Patient.php", r'''
<?php

namespace App\Modules\Patients\Models;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Prescriptions\Models\Prescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Patient extends Model implements AuditableContract
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'document_type', 'document_number', 'first_name', 'last_name',
        'birth_date', 'gender', 'email', 'phone', 'address',
        'blood_type', 'allergies', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
''')

w("app/Modules/Patients/Models/PatientContact.php", r'''
<?php

namespace App\Modules\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContact extends Model
{
    protected $fillable = [
        'patient_id', 'name', 'relationship', 'phone', 'email', 'is_emergency',
    ];

    protected function casts(): array
    {
        return ['is_emergency' => 'boolean'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
''')

w("app/Modules/Doctors/Models/Doctor.php", r'''
<?php

namespace App\Modules\Doctors\Models;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Catalogs\Models\Specialty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Doctor extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['user_id', 'license_number', 'bio', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
''')

w("app/Modules/Doctors/Models/DoctorSchedule.php", r'''
<?php

namespace App\Modules\Doctors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    protected $fillable = [
        'doctor_id', 'weekday', 'start_time', 'end_time', 'slot_minutes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'slot_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
''')

w("app/Modules/Appointments/Models/Appointment.php", r'''
<?php

namespace App\Modules\Appointments\Models;

use App\Models\User;
use App\Modules\Catalogs\Models\Specialty;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Appointment extends Model implements AuditableContract
{
    use Auditable;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'folio', 'patient_id', 'doctor_id', 'specialty_id',
        'starts_at', 'ends_at', 'status', 'reason', 'notes',
        'cancelled_at', 'cancellation_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_CONFIRMED], true);
    }
}
''')

w("app/Modules/Consultations/Models/Consultation.php", r'''
<?php

namespace App\Modules\Consultations\Models;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use App\Modules\Prescriptions\Models\Prescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Consultation extends Model implements AuditableContract
{
    use Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'folio', 'appointment_id', 'patient_id', 'doctor_id', 'attended_at',
        'chief_complaint', 'diagnosis', 'treatment', 'observations', 'status',
    ];

    protected function casts(): array
    {
        return ['attended_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
''')

w("app/Modules/Consultations/Models/VitalSign.php", r'''
<?php

namespace App\Modules\Consultations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    protected $fillable = [
        'consultation_id', 'recorded_at', 'weight_kg', 'height_cm',
        'bp_systolic', 'bp_diastolic', 'heart_rate', 'temperature_c', 'spo2', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'temperature_c' => 'decimal:1',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
''')

w("app/Modules/Prescriptions/Models/Prescription.php", r'''
<?php

namespace App\Modules\Prescriptions\Models;

use App\Modules\Consultations\Models\Consultation;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Prescription extends Model implements AuditableContract
{
    use Auditable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'folio', 'consultation_id', 'patient_id', 'doctor_id',
        'issued_at', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
''')

w("app/Modules/Prescriptions/Models/PrescriptionItem.php", r'''
<?php

namespace App\Modules\Prescriptions\Models;

use App\Modules\Catalogs\Models\Medicine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id', 'medicine_id', 'medicine_name',
        'dosage', 'frequency', 'duration', 'instructions',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
''')

w("app/Modules/Payments/Models/Payment.php", r'''
<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Consultations\Models\Consultation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Payment extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = [
        'consultation_id', 'appointment_id', 'amount', 'currency',
        'payment_method_id', 'paid_at', 'reference', 'recorded_by', 'notes',
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

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
''')

print("models done")
