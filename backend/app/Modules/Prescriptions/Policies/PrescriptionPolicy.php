<?php

namespace App\Modules\Prescriptions\Policies;

use App\Models\User;
use App\Modules\Prescriptions\Models\Prescription;
use App\Support\Auth\ClinicActor;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('prescriptions.view');
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.view')
            && ClinicActor::ownsDoctorId($user, $prescription->doctor_id);
    }

    public function create(User $user): bool
    {
        return $user->can('prescriptions.create');
    }

    public function cancel(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.cancel')
            && ClinicActor::ownsDoctorId($user, $prescription->doctor_id);
    }

    public function export(User $user, Prescription $prescription): bool
    {
        return ($user->can('prescriptions.export') || $user->can('prescriptions.view'))
            && ClinicActor::ownsDoctorId($user, $prescription->doctor_id);
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return false;
    }
}
