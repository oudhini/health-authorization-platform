<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Domain\Coverage\ActPricing;
use App\Domain\Coverage\CoverageCalculator;
use App\Domain\Requests\Exceptions\CannotCreateRequest;
use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use App\Enums\AuditAction;
use App\Models\AuthorizationRequest;
use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UpdateAuthorizationRequest
{
    public function __construct(
        private readonly CoverageCalculator $calculator,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<array{medical_act_id: int, billed_amount: int}>  $acts
     */
    public function handle(User $agent, AuthorizationRequest $request, array $acts): AuthorizationRequest
    {
        return DB::transaction(function () use ($agent, $request, $acts): AuthorizationRequest {
            $locked = AuthorizationRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== RequestStatus::Draft) {
                throw InvalidStatusTransition::between($locked->status, RequestStatus::Draft);
            }

            $previousActIds = array_map('intval', $locked->items()->pluck('medical_act_id')->all());
            $today = CarbonImmutable::today();
            $pricedItems = [];

            foreach ($acts as $actInput) {
                $act = MedicalAct::query()->find($actInput['medical_act_id']);
                if ($act === null || (! $act->is_active && ! in_array($act->id, $previousActIds, true))) {
                    throw CannotCreateRequest::invalidCoverageSelection();
                }

                $tariff = OfficialTariff::amountFor($act->id, $today)
                    ?? throw CannotCreateRequest::missingTariff($act->label);
                $result = $this->calculator->calculateAct(
                    new ActPricing($actInput['billed_amount'], $tariff),
                    $locked->coverage_rate,
                );

                $pricedItems[] = [
                    'medical_act_id' => $act->id,
                    'billed_amount' => $result->billedAmount,
                    'official_tariff' => $tariff,
                    'accepted_amount' => $result->acceptedAmount,
                    'insurer_share' => $result->insurerShare,
                    'patient_share' => $result->patientShare,
                    'overrun' => $result->overrun,
                ];
            }

            $locked->items()->delete();
            foreach ($pricedItems as $item) {
                $locked->items()->create($item);
            }

            $this->audit->record(AuditAction::RequestUpdated, $agent, $locked, [
                'item_count' => count($pricedItems),
            ]);

            return $locked->load('items');
        });
    }
}
