<?php

namespace App\Support\Auth;

use App\Models\User;
use App\Modules\Doctors\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;

class ClinicActor
{
    public static function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public static function isDoctor(User $user): bool
    {
        return $user->hasRole('doctor');
    }

    public static function doctorIdFor(User $user): ?int
    {
        $id = Doctor::query()->where('user_id', $user->id)->value('id');

        return $id !== null ? (int) $id : null;
    }

    public static function ownsDoctorId(User $user, ?int $doctorId): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if (! self::isDoctor($user) || $doctorId === null) {
            return false;
        }

        $mine = self::doctorIdFor($user);

        return $mine !== null && $mine === (int) $doctorId;
    }

    /**
     * Restringe listados clínicos al médico autenticado (admin y recepción sin filtro).
     */
    public static function scopeOwnDoctor(Builder $query, User $user, string $column = 'doctor_id'): Builder
    {
        if (self::isAdmin($user) || ! self::isDoctor($user)) {
            return $query;
        }

        $doctorId = self::doctorIdFor($user);

        return $query->where($column, $doctorId ?? 0);
    }
}
