<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

use App\Domain\Requests\RequestStatus;
use DomainException;

/** Raised when code tries to move a request to a status the lifecycle forbids. */
final class InvalidStatusTransition extends DomainException
{
    public static function between(RequestStatus $from, RequestStatus $to): self
    {
        return new self("Transition from '{$from->value}' to '{$to->value}' is not allowed.");
    }
}
