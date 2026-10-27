<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Domain\Coverage\ActPricing;
use App\Domain\Coverage\CoverageCalculator;
use App\Domain\Requests\Exceptions\CannotCreateRequest;
use App\Domain\Requests\RequestStatus;
use App\Enums\AuditAction;
use App\Models\AuthorizationRequest;
use App\Models\Beneficiary;
use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates a draft request with its priced acts (workflow 1, steps 1 to 4).
 *
 * The facility comes from the agent. The selected insurer, beneficiary and
 * active policy are verified against the facility's configured partnerships.
 */
final class CreateAuthorizationRequest
{
    public function __construct(
        private readonly CoverageCalculator $calculator,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<array{medical_act_id: int, billed_amount: int}>  $acts
     *
     * @throws CannotCreateRequest
     */
    public function handle(
        User $agent,
        int $insurerId,
        int $beneficiaryId,
        int $policyId,
        array $acts,
        ?int $relatedRequestId = null,
    ): AuthorizationRequest {
        $today = CarbonImmutable::today();

        $facility = Organization::query()->find($agent->organization_id);
        $isPartner = $facility !== null
            && $facility->partnerInsurers()->whereKey($insurerId)->exists();

        if (! $isPartner) {
            throw CannotCreateRequest::insurerNotPartnered();
        }

        $beneficiary = Beneficiary::query()
            ->with('policyholder')
            ->where('insurer_id', $insurerId)
            ->find($beneficiaryId) ?? throw CannotCreateRequest::unknownBeneficiary();

        $policy = Policy::query()
            ->whereKey($policyId)
            ->where('insurer_id', $insurerId)
            ->where('subscriber_id', $beneficiary->policyholder->subscriber_id)
            ->activeOn($today)
            ->first() ?? throw CannotCreateRequest::invalidCoverageSelection();

        $related = $relatedRequestId === null ? null : $this->resolveRelatedRequest(
            $agent,
            $beneficiary,
            $insurerId,
            $relatedRequestId,
        );

        return DB::transaction(function () use ($agent, $beneficiary, $policy, $related, $acts, $today): AuthorizationRequest {
            $request = AuthorizationRequest::create([
                'facility_id' => $agent->organization_id,
                'insurer_id' => $policy->insurer_id,
                'beneficiary_id' => $beneficiary->id,
                'policy_id' => $policy->id,
                'related_request_id' => $related?->id,
                'status' => RequestStatus::Draft,
                // Copy of the rate: a later policy change must not alter this request.
                'coverage_rate' => $policy->coverage_rate,
                'created_by' => $agent->id,
            ]);

            foreach ($acts as $act) {
                if (! MedicalAct::query()->whereKey($act['medical_act_id'])->where('is_active', true)->exists()) {
                    throw CannotCreateRequest::inactiveMedicalAct();
                }

                $tariff = OfficialTariff::amountFor($act['medical_act_id'], $today)
                    ?? throw CannotCreateRequest::missingTariff($this->actLabel($act['medical_act_id']));

                $result = $this->calculator->calculateAct(
                    new ActPricing($act['billed_amount'], $tariff),
                    $policy->coverage_rate,
                );

                $request->items()->create([
                    'medical_act_id' => $act['medical_act_id'],
                    'billed_amount' => $result->billedAmount,
                    'official_tariff' => $tariff,
                    'accepted_amount' => $result->acceptedAmount,
                    'insurer_share' => $result->insurerShare,
                    'patient_share' => $result->patientShare,
                    'overrun' => $result->overrun,
                ]);
            }

            $this->audit->record(AuditAction::RequestCreated, $agent, $request);

            return $request;
        });
    }

    private function resolveRelatedRequest(
        User $agent,
        Beneficiary $beneficiary,
        int $insurerId,
        int $relatedRequestId,
    ): AuthorizationRequest {
        $related = AuthorizationRequest::query()->find($relatedRequestId);

        $valid = $related !== null
            && (int) $related->facility_id === (int) $agent->organization_id
            && (int) $related->beneficiary_id === (int) $beneficiary->id
            && (int) $related->insurer_id === $insurerId
            && $related->status === RequestStatus::Approved;

        return $valid ? $related : throw CannotCreateRequest::invalidRelatedRequest();
    }

    private function actLabel(int $medicalActId): string
    {
        return MedicalAct::query()->find($medicalActId)?->label ?? '#'.$medicalActId;
    }
}
