<?php

namespace App\Modules\Appointments\Policies;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Support\Auth\ClinicActor;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if (! $user->can('appointments.view')) {
            return false;
        }

        if (ClinicActor::isDoctor($user) && ! ClinicActor::isAdmin($user)) {
            return ClinicActor::ownsDoctorId($user, $appointment->doctor_id);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if (! $user->can('appointments.create') && ! $user->can('appointments.reschedule')) {
            return false;
        }

        if (ClinicActor::isDoctor($user) && ! ClinicActor::isAdmin($user)) {
            return ClinicActor::ownsDoctorId($user, $appointment->doctor_id);
        }

        return true;
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        if (! $user->can('appointments.reschedule')) {
            return false;
        }

        if (ClinicActor::isDoctor($user) && ! ClinicActor::isAdmin($user)) {
            return ClinicActor::ownsDoctorId($user, $appointment->doctor_id);
        }

        return true;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if (! $user->can('appointments.cancel')) {
            return false;
        }

        if (ClinicActor::isDoctor($user) && ! ClinicActor::isAdmin($user)) {
            return ClinicActor::ownsDoctorId($user, $appointment->doctor_id);
        }

        return true;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
