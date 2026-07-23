<?php

namespace App\Modules\Consultations\Policies;

use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Support\Auth\ClinicActor;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.view')
            && ClinicActor::ownsDoctorId($user, $consultation->doctor_id);
    }

    public function create(User $user): bool
    {
        return $user->can('consultations.create');
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.update')
            && ClinicActor::ownsDoctorId($user, $consultation->doctor_id);
    }

    public function finalize(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.finalize')
            && ClinicActor::ownsDoctorId($user, $consultation->doctor_id);
    }

    public function delete(User $user, Consultation $consultation): bool
    {
        return false;
    }
}
