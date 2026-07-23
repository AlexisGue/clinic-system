<?php

namespace App\Support\Exceptions;

use Exception;

/**
 * Base class for business-rule violations (e.g. insufficient stock,
 * closed cash session). Services throw these instead of returning
 * false/null; the global handler renders them as consistent JSON.
 */
abstract class DomainException extends Exception
{
    protected int $status = 422;

    protected string $errorCode = 'DOMAIN_ERROR';

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
