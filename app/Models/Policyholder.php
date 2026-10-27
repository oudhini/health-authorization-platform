<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The employee holding the contract (assuré principal). */
#[Fillable(['subscriber_id', 'name'])]
class Policyholder extends Model
{
    use HasFactory;

    /** @return BelongsTo<Subscriber, $this> */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    /** @return HasMany<Beneficiary, $this> */
    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }
}
