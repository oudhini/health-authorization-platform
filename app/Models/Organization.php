<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type'])]
class Organization extends Model
{
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => OrganizationType::class];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Insurers with which this facility has an active working relationship. */
    public function partnerInsurers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'facility_insurer', 'facility_id', 'insurer_id')
            ->withTimestamps();
    }
}
