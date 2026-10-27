<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The authorization (bon de prise en charge) produced by an approval.
 * It is created once and never modified.
 */
#[Fillable([
    'authorization_request_id', 'valid_from', 'valid_until',
    'billed_total', 'insurer_total', 'patient_total', 'overrun_total', 'decided_by',
])]
class Authorization extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'billed_total' => 'integer',
            'insurer_total' => 'integer',
            'patient_total' => 'integer',
            'overrun_total' => 'integer',
        ];
    }

    /**
     * Sequential number derived from the id, so it can never collide or change.
     *
     * @return Attribute<string, never>
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('BPC-%06d', $this->id));
    }

    /** @return BelongsTo<AuthorizationRequest, $this> */
    public function authorizationRequest(): BelongsTo
    {
        return $this->belongsTo(AuthorizationRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** 'not_started', 'active' or 'expired', computed from the dates, never stored. */
    public function validityStatus(CarbonInterface $on): string
    {
        $day = $on->toDateString();

        if ($day < $this->valid_from->toDateString()) {
            return 'not_started';
        }

        return $day > $this->valid_until->toDateString() ? 'expired' : 'active';
    }
}
