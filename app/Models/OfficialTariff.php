<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['medical_act_id', 'amount', 'effective_from'])]
class OfficialTariff extends Model
{
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'effective_from' => 'date',
        ];
    }

    /** @return BelongsTo<MedicalAct, $this> */
    public function medicalAct(): BelongsTo
    {
        return $this->belongsTo(MedicalAct::class);
    }

    /**
     * Official amount of an act on a given day: the most recent tariff that
     * started on or before that day. Null when no tariff applies yet.
     */
    public static function amountFor(int $medicalActId, CarbonInterface $on): ?int
    {
        $amount = self::query()
            ->where('medical_act_id', $medicalActId)
            ->whereDate('effective_from', '<=', $on)
            ->orderByDesc('effective_from')
            ->value('amount');

        return $amount === null ? null : (int) $amount;
    }
}
