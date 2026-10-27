<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['number', 'insurer_id', 'subscriber_id', 'coverage_rate', 'starts_on', 'ends_on'])]
class Policy extends Model
{
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'coverage_rate' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'insurer_id');
    }

    /** @return BelongsTo<Subscriber, $this> */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    /**
     * Policies in force on a given day, both bounds included.
     * whereDate compares calendar days only, whatever the database stores
     * (SQLite keeps "2026-10-16 00:00:00" for date casts, which a plain
     * comparison with "2026-10-16" would get wrong on the boundary day).
     *
     * @param Builder<Policy> $query
     * @return Builder<Policy>
     */
    public function scopeActiveOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date);
    }

    public function isActiveOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return $this->starts_on->toDateString() <= $day && $this->ends_on->toDateString() >= $day;
    }
}
