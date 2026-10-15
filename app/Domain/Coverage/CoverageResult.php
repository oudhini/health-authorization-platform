<?php

declare(strict_types=1);

namespace App\Domain\Coverage;

/**
 * Outcome of a coverage calculation, for one act or for a whole authorization.
 *
 * Note: $overrun is informational. It is already included in $patientShare
 * and must never be added to it a second time.
 */
final readonly class CoverageResult
{
    public function __construct(
        public int $billedAmount,
        public int $acceptedAmount,
        public int $insurerShare,
        public int $patientShare,
        public int $overrun,
    ) {
    }

    /** Totals of an authorization are the sums of the per-act results. */
    public static function sum(self ...$results): self
    {
        $billed = 0;
        $accepted = 0;
        $insurer = 0;
        $patient = 0;
        $overrun = 0;

        foreach ($results as $result) {
            $billed += $result->billedAmount;
            $accepted += $result->acceptedAmount;
            $insurer += $result->insurerShare;
            $patient += $result->patientShare;
            $overrun += $result->overrun;
        }

        return new self($billed, $accepted, $insurer, $patient, $overrun);
    }
}
