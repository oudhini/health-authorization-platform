<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

use DomainException;

/** A request failed the conditions of section 5.2 for the draft to submitted transition. */
final class CannotSubmitRequest extends DomainException
{
    /** @param list<string> $reasons */
    private function __construct(private readonly array $reasons)
    {
        parent::__construct(implode(' ', $reasons));
    }

    /** @param list<string> $reasons */
    public static function because(array $reasons): self
    {
        return new self($reasons);
    }

    /** @return list<string> */
    public function reasons(): array
    {
        return $this->reasons;
    }
}
