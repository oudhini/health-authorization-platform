<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfficialTariff> */
class OfficialTariffFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'medical_act_id' => MedicalAct::factory(),
            'amount' => 10000,
            'effective_from' => '2000-01-01',
        ];
    }
}
