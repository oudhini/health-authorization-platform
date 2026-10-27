<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The person receiving care (the policyholder or a dependant). */
#[Fillable(['policyholder_id', 'insurer_id', 'name', 'birth_date', 'relationship', 'member_number'])]
class Beneficiary extends Model
{
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    /** @return BelongsTo<Policyholder, $this> */
    public function policyholder(): BelongsTo
    {
        return $this->belongsTo(Policyholder::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'insurer_id');
    }

    /**
     * Convenience lookup only; authorization requests must use an explicitly
     * selected policy scoped to the chosen insurer instead.
     */
    public function activePolicy(CarbonInterface $on): ?Policy
    {
        return Policy::query()
            ->where('subscriber_id', $this->policyholder->subscriber_id)
            ->activeOn($on)
            ->orderByDesc('starts_on')
            ->first();
    }
}
