<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Coverage\CoverageResult;
use App\Domain\Requests\RequestStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'facility_id', 'insurer_id', 'beneficiary_id', 'policy_id', 'related_request_id',
    'status', 'coverage_rate', 'created_by', 'decided_by', 'decision_note',
    'submitted_at', 'decided_at',
])]
class AuthorizationRequest extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'coverage_rate' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'facility_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'insurer_id');
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /** @return BelongsTo<Policy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /** @return BelongsTo<self, $this> */
    public function relatedRequest(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_request_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return HasMany<AuthorizationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AuthorizationItem::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<AuthorizationRequestMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(AuthorizationRequestMessage::class)->orderBy('id');
    }

    /** @return HasOne<Authorization, $this> */
    public function authorization(): HasOne
    {
        return $this->hasOne(Authorization::class);
    }

    /** The facility may still change a request in these two statuses only. */
    public function isEditable(): bool
    {
        return in_array($this->status, [RequestStatus::Draft, RequestStatus::InfoRequested], true);
    }

    /**
     * Documents the request must carry before it can be submitted.
     * An exam request (linked to a consultation) needs the signed medical form.
     *
     * @return list<DocumentType>
     */
    public function requiredDocumentTypes(): array
    {
        return $this->related_request_id === null ? [] : [DocumentType::MedicalForm];
    }

    /** @return list<DocumentType> */
    public function missingDocumentTypes(): array
    {
        $present = $this->documents->pluck('type')->all();

        return array_values(array_filter(
            $this->requiredDocumentTypes(),
            fn (DocumentType $type): bool => ! in_array($type, $present, true),
        ));
    }

    /** Totals of the request: sums of the per-act amounts stored on its items. */
    public function coverageTotals(): CoverageResult
    {
        return new CoverageResult(
            billedAmount: (int) $this->items->sum('billed_amount'),
            acceptedAmount: (int) $this->items->sum('accepted_amount'),
            insurerShare: (int) $this->items->sum('insurer_share'),
            patientShare: (int) $this->items->sum('patient_share'),
            overrun: (int) $this->items->sum('overrun'),
        );
    }
}
