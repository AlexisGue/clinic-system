<?php

namespace App\Modules\Prescriptions\Exceptions;

use App\Support\Exceptions\DomainException;

class PrescriptionDomainException extends DomainException
{
    protected int $status = 422;

    protected string $errorCode = 'PRESCRIPTION_ERROR';

    public static function emptyItems(): self
    {
        $e = new self('La receta debe incluir al menos un medicamento.');
        $e->errorCode = 'PRESCRIPTION_EMPTY_ITEMS';

        return $e;
    }

    public static function notCancellable(): self
    {
        $e = new self('La receta no se puede cancelar.');
        $e->errorCode = 'PRESCRIPTION_NOT_CANCELLABLE';

        return $e;
    }
}
