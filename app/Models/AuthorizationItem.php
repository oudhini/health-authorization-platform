<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One medical act on a request, with its amounts frozen at creation. */
#[Fillable([
    'authorization_request_id', 'medical_act_id', 'billed_amount', 'official_tariff',
    'accepted_amount', 'insurer_share', 'patient_share', 'overrun',
])]
class AuthorizationItem extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'billed_amount' => 'integer',
            'official_tariff' => 'integer',
            'accepted_amount' => 'integer',
            'insurer_share' => 'integer',
            'patient_share' => 'integer',
            'overrun' => 'integer',
        ];
    }

    /** @return BelongsTo<AuthorizationRequest, $this> */
    public function authorizationRequest(): BelongsTo
    {
        return $this->belongsTo(AuthorizationRequest::class);
    }

    /** @return BelongsTo<MedicalAct, $this> */
    public function medicalAct(): BelongsTo
    {
        return $this->belongsTo(MedicalAct::class);
    }
}
