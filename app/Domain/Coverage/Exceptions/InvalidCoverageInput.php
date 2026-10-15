<?php

declare(strict_types=1);

namespace App\Domain\Coverage\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a coverage calculation receives data that breaks the business
 * invariants (negative amount, rate outside 0-100). It is a programming or
 * data error, never something the end user should be able to trigger with
 * valid form input: forms must validate before reaching the domain.
 */
final class InvalidCoverageInput extends InvalidArgumentException
{
    public static function negativeAmount(): self
    {
        return new self('Amounts must be zero or positive integers (FCFA has no decimals).');
    }

    public static function rateOutOfRange(int $rate): self
    {
        return new self("Coverage rate must be between 0 and 100, got {$rate}.");
    }
}
