<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The company that subscribes a contract for its employees. */
#[Fillable(['insurer_id', 'name'])]
class Subscriber extends Model
{
    use HasFactory;

    /** @return BelongsTo<Organization, $this> */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'insurer_id');
    }

    /** @return HasMany<Policyholder, $this> */
    public function policyholders(): HasMany
    {
        return $this->hasMany(Policyholder::class);
    }

    /** @return HasMany<Policy, $this> */
    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }
}
