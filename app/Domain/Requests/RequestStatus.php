<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Requests\Exceptions\InvalidStatusTransition;

/**
 * Lifecycle of an authorization request (docs/requirements.md, section 5).
 *
 * This enum only answers "is this status change possible?". Who is allowed to
 * trigger it (facility_agent or insurer_manager) is an authorization concern,
 * handled later by Laravel policies, not here.
 */
enum RequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InfoRequested = 'info_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Cancelled],
            self::Submitted => [self::Approved, self::Rejected, self::InfoRequested],
            self::InfoRequested => [self::Submitted, self::Cancelled],
            self::Approved, self::Rejected, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * @throws InvalidStatusTransition when the lifecycle forbids the change
     */
    public function transitionTo(self $target): self
    {
        if (! $this->canTransitionTo($target)) {
            throw InvalidStatusTransition::between($this, $target);
        }

        return $target;
    }
}
