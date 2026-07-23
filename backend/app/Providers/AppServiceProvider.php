<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Policies\AppointmentPolicy;
use App\Modules\Audit\Policies\AuditPolicy;
use App\Modules\Catalogs\Models\Medicine;
use App\Modules\Catalogs\Models\Specialty;
use App\Modules\Catalogs\Policies\MedicinePolicy;
use App\Modules\Catalogs\Policies\SpecialtyPolicy;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Policies\ConsultationPolicy;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Doctors\Policies\DoctorPolicy;
use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Policies\PatientPolicy;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Policies\PaymentPolicy;
use App\Modules\Prescriptions\Models\Prescription;
use App\Modules\Prescriptions\Policies\PrescriptionPolicy;
use App\Modules\Users\Policies\RolePolicy;
use App\Modules\Users\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use OwenIt\Auditing\Models\Audit;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers()
        );

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Audit::class, AuditPolicy::class);
        Gate::policy(Specialty::class, SpecialtyPolicy::class);
        Gate::policy(Medicine::class, MedicinePolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Doctor::class, DoctorPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Consultation::class, ConsultationPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
