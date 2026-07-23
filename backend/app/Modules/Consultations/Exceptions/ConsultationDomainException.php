<?php

namespace App\Modules\Consultations\Exceptions;

use App\Support\Exceptions\DomainException;

class ConsultationDomainException extends DomainException
{
    protected int $status = 422;

    protected string $errorCode = 'CONSULTATION_ERROR';

    public static function alreadyFinalized(): self
    {
        $e = new self('La consulta ya está finalizada y no se puede editar.');
        $e->errorCode = 'CONSULTATION_ALREADY_FINALIZED';

        return $e;
    }

    public static function appointmentAlreadyLinked(): self
    {
        $e = new self('La cita ya tiene una consulta asociada.');
        $e->errorCode = 'CONSULTATION_APPOINTMENT_LINKED';

        return $e;
    }

    public static function paymentRequired(): self
    {
        $e = new self('Se requiere un pago antes de finalizar la consulta.');
        $e->errorCode = 'CONSULTATION_PAYMENT_REQUIRED';

        return $e;
    }
}
