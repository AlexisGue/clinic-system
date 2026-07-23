<?php

namespace App\Modules\Appointments\Exceptions;

use App\Support\Exceptions\DomainException;

class AppointmentDomainException extends DomainException
{
    protected int $status = 422;

    protected string $errorCode = 'APPOINTMENT_ERROR';

    public static function overlap(): self
    {
        $e = new self('El médico ya tiene una cita en ese horario.');
        $e->errorCode = 'APPOINTMENT_OVERLAP';

        return $e;
    }

    public static function outsideSchedule(): self
    {
        $e = new self('La cita está fuera del horario del médico para ese día.');
        $e->errorCode = 'APPOINTMENT_OUTSIDE_SCHEDULE';

        return $e;
    }

    public static function notCancellable(): self
    {
        $e = new self('La cita no se puede cancelar en su estado actual.');
        $e->errorCode = 'APPOINTMENT_NOT_CANCELLABLE';

        return $e;
    }

    public static function invalidStatus(string $status): self
    {
        $e = new self("Estado de cita no válido: {$status}.");
        $e->errorCode = 'APPOINTMENT_INVALID_STATUS';

        return $e;
    }
}
