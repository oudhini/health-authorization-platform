<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'label'])]
class MedicalAct extends Model
{
    use HasFactory;

    /** @return HasMany<OfficialTariff, $this> */
    public function tariffs(): HasMany
    {
        return $this->hasMany(OfficialTariff::class);
    }
}
