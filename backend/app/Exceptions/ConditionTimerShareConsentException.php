<?php

namespace App\Exceptions;

use Illuminate\Support\Collection;
use RuntimeException;

class ConditionTimerShareConsentException extends RuntimeException
{
    /**
     * @param  Collection<int, array<string, mixed>>  $missing
     */
    public function __construct(public readonly Collection $missing)
    {
        parent::__construct('Consent requirements are not satisfied for the requested visibility mode.');
    }
}
