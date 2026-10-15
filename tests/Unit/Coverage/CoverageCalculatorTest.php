<?php

declare(strict_types=1);

namespace Tests\Unit\Coverage;

use App\Domain\Coverage\ActPricing;
use App\Domain\Coverage\CoverageCalculator;
use App\Domain\Coverage\Exceptions\InvalidCoverageInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Executable version of docs/requirements.md, section 6.
 * All values are fictional.
 */
final class CoverageCalculatorTest extends TestCase
{
    private CoverageCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CoverageCalculator();
    }

    /**
     * Reference cases A to E of section 6.4.
     * Cases A, D and E rest on assumptions still open in section 10
     * (questions 1 and 2). If the field answers differ, change the case here
     * and in the requirements together.
     *
     * @return array<string, array{int, int, int, int, int, int, int}>
     */
    public static function referenceCases(): array
    {
        return [
            // billed, tariff, rate, accepted, insurer, patient, overrun
            'A. facility cheaper than tariff' => [8000, 10000, 100, 8000, 8000, 0, 0],
            'B. price equals tariff' => [10000, 10000, 100, 10000, 10000, 0, 0],
            'C. overrun at 100%' => [12000, 10000, 100, 10000, 10000, 2000, 2000],
            'D. 80%, no overrun' => [10000, 10000, 80, 10000, 8000, 2000, 0],
            'E. 80%, with overrun' => [12000, 10000, 80, 10000, 8000, 4000, 2000],
        ];
    }

    #[DataProvider('referenceCases')]
    public function test_reference_cases(
        int $billed,
        int $tariff,
        int $rate,
        int $accepted,
        int $insurer,
        int $patient,
        int $overrun,
    ): void {
        $result = $this->calculator->calculateAct(new ActPricing($billed, $tariff), $rate);

        $this->assertSame($billed, $result->billedAmount);
        $this->assertSame($accepted, $result->acceptedAmount);
        $this->assertSame($insurer, $result->insurerShare);
        $this->assertSame($patient, $result->patientShare);
        $this->assertSame($overrun, $result->overrun);
    }

    /**
     * Rounding is half up, computed on integers (open question 3, proposal).
     *
     * @return array<string, array{int, int, int, int, int}>
     */
    public static function roundingCases(): array
    {
        return [
            // accepted amount, rate, expected insurer, expected patient, billed
            '500.5 rounds up to 501' => [1001, 50, 501, 500, 1001],
            '0.5 rounds up to 1' => [50, 1, 1, 49, 50],
            '0.49 rounds down to 0' => [49, 1, 0, 49, 49],
            '330.33 rounds down to 330' => [1001, 33, 330, 671, 1001],
            'exact value, no rounding' => [1000, 33, 330, 670, 1000],
        ];
    }

    #[DataProvider('roundingCases')]
    public function test_rounding_is_half_up(int $amount, int $rate, int $insurer, int $patient, int $billed): void
    {
        $result = $this->calculator->calculateAct(new ActPricing($billed, $amount), $rate);

        $this->assertSame($insurer, $result->insurerShare);
        $this->assertSame($patient, $result->patientShare);
    }

    public function test_rate_zero_leaves_everything_to_the_patient(): void
    {
        $result = $this->calculator->calculateAct(new ActPricing(5000, 5000), 0);

        $this->assertSame(0, $result->insurerShare);
        $this->assertSame(5000, $result->patientShare);
    }

    public function test_zero_billed_amount_gives_zero_everywhere(): void
    {
        $result = $this->calculator->calculateAct(new ActPricing(0, 10000), 80);

        $this->assertSame(0, $result->acceptedAmount);
        $this->assertSame(0, $result->insurerShare);
        $this->assertSame(0, $result->patientShare);
        $this->assertSame(0, $result->overrun);
    }

    /** Invariants of section 6.3 must hold for every combination below. */
    public function test_invariants_hold_for_many_combinations(): void
    {
        foreach ([0, 1, 99, 100, 1001, 12000] as $billed) {
            foreach ([0, 1, 50, 10000] as $tariff) {
                foreach ([0, 1, 33, 50, 80, 99, 100] as $rate) {
                    $result = $this->calculator->calculateAct(new ActPricing($billed, $tariff), $rate);
                    $context = "billed={$billed} tariff={$tariff} rate={$rate}";

                    $this->assertSame($billed, $result->insurerShare + $result->patientShare, $context);
                    $this->assertGreaterThanOrEqual(0, $result->insurerShare, $context);
                    $this->assertLessThanOrEqual($result->acceptedAmount, $result->insurerShare, $context);
                    $this->assertLessThanOrEqual($billed, $result->acceptedAmount, $context);
                    $this->assertSame(max(0, $billed - $tariff), $result->overrun, $context);
                }
            }
        }
    }

    public function test_authorization_totals_are_the_sum_of_per_act_results(): void
    {
        $acts = [
            new ActPricing(12000, 10000), // 80%: accepted 10000, insurer 8000, patient 4000
            new ActPricing(8000, 10000),  // 80%: accepted 8000, insurer 6400, patient 1600
        ];

        $total = $this->calculator->calculateAuthorization($acts, 80);

        $this->assertSame(20000, $total->billedAmount);
        $this->assertSame(18000, $total->acceptedAmount);
        $this->assertSame(14400, $total->insurerShare);
        $this->assertSame(5600, $total->patientShare);
        $this->assertSame(2000, $total->overrun);
    }

    /**
     * Guards the rule "per act, never on the total". Computed on the total,
     * 12000 + 8000 billed against 10000 + 10000 of tariffs would let the
     * insurer pay 20000. Computed per act, the insurer pays 18000.
     */
    public function test_rules_are_applied_per_act_not_on_the_total(): void
    {
        $acts = [
            new ActPricing(12000, 10000),
            new ActPricing(8000, 10000),
        ];

        $total = $this->calculator->calculateAuthorization($acts, 100);

        $this->assertSame(18000, $total->insurerShare);
        $this->assertSame(2000, $total->patientShare);
    }

    public function test_authorization_without_acts_is_all_zero(): void
    {
        $total = $this->calculator->calculateAuthorization([], 80);

        $this->assertSame(0, $total->billedAmount);
        $this->assertSame(0, $total->insurerShare);
        $this->assertSame(0, $total->patientShare);
    }

    public function test_negative_billed_amount_is_rejected(): void
    {
        $this->expectException(InvalidCoverageInput::class);

        new ActPricing(-1, 10000);
    }

    public function test_negative_tariff_is_rejected(): void
    {
        $this->expectException(InvalidCoverageInput::class);

        new ActPricing(1000, -1);
    }

    /** @return array<string, array{int}> */
    public static function invalidRates(): array
    {
        return [
            'below zero' => [-1],
            'above one hundred' => [101],
        ];
    }

    #[DataProvider('invalidRates')]
    public function test_rate_outside_zero_to_one_hundred_is_rejected(int $rate): void
    {
        $this->expectException(InvalidCoverageInput::class);

        $this->calculator->calculateAct(new ActPricing(1000, 1000), $rate);
    }

    public function test_invalid_rate_is_rejected_even_without_acts(): void
    {
        $this->expectException(InvalidCoverageInput::class);

        $this->calculator->calculateAuthorization([], 150);
    }
}
