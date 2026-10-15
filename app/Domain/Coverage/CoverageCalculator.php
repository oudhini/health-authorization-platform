<?php

declare(strict_types=1);

namespace App\Domain\Coverage;

use App\Domain\Coverage\Exceptions\InvalidCoverageInput;

/**
 * Implements the coverage rules of docs/requirements.md, section 6.
 *
 * Pure PHP on purpose: no database, no framework. This is the core business
 * rule of the platform, so it must be testable in isolation and fast.
 */
final class CoverageCalculator
{
    /**
     * Rules, applied per act and never on the total:
     *   accepted_amount = min(billed_amount, official_tariff)
     *   insurer_share   = round(accepted_amount * rate / 100), half up
     *   patient_share   = billed_amount - insurer_share
     *   overrun         = max(0, billed_amount - official_tariff)
     */
    public function calculateAct(ActPricing $act, int $coverageRatePercent): CoverageResult
    {
        $this->assertValidRate($coverageRatePercent);

        $accepted = min($act->billedAmount, $act->officialTariff);

        // Integer arithmetic only. Adding 50 before the integer division by
        // 100 rounds half up for non-negative numbers (e.g. 500.5 -> 501)
        // without ever touching floats.
        $insurerShare = intdiv($accepted * $coverageRatePercent + 50, 100);

        // Derived by subtraction so that insurer + patient always equals the
        // billed amount, whatever the rounding does.
        $patientShare = $act->billedAmount - $insurerShare;

        $overrun = max(0, $act->billedAmount - $act->officialTariff);

        return new CoverageResult(
            billedAmount: $act->billedAmount,
            acceptedAmount: $accepted,
            insurerShare: $insurerShare,
            patientShare: $patientShare,
            overrun: $overrun,
        );
    }

    /**
     * Totals of an authorization: each act is calculated on its own, then the
     * results are summed (rule 5 of section 6.2).
     *
     * @param list<ActPricing> $acts
     */
    public function calculateAuthorization(array $acts, int $coverageRatePercent): CoverageResult
    {
        $this->assertValidRate($coverageRatePercent);

        return CoverageResult::sum(
            ...array_map(
                fn (ActPricing $act): CoverageResult => $this->calculateAct($act, $coverageRatePercent),
                $acts,
            ),
        );
    }

    private function assertValidRate(int $coverageRatePercent): void
    {
        if ($coverageRatePercent < 0 || $coverageRatePercent > 100) {
            throw InvalidCoverageInput::rateOutOfRange($coverageRatePercent);
        }
    }
}
