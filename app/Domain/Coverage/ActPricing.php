<?php

declare(strict_types=1);

namespace App\Domain\Coverage;

use App\Domain\Coverage\Exceptions\InvalidCoverageInput;

/**
 * One medical act as priced on an authorization request.
 *
 * Amounts are integers in FCFA. Floats are never used for money: they cannot
 * represent every decimal value exactly and would break the invariant
 * insurer_share + patient_share = billed_amount.
 */
final readonly class ActPricing
{
    public function __construct(
        public int $billedAmount,
        public int $officialTariff,
    ) {
        if ($billedAmount < 0 || $officialTariff < 0) {
            throw InvalidCoverageInput::negativeAmount();
        }
    }
}
