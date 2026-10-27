<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Beneficiary;
use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\Policyholder;
use App\Models\Subscriber;
use App\Models\User;

/**
 * A complete fictional world for feature tests: two facilities, two insurers,
 * one user per role, one covered beneficiary and two priced medical acts.
 */
final class Scenario
{
    public Organization $facility;
    public Organization $otherFacility;
    public Organization $insurer;
    public Organization $otherInsurer;
    public User $agent;
    public User $otherAgent;
    public User $manager;
    public User $otherManager;
    public User $admin;
    public Subscriber $subscriber;
    public Beneficiary $beneficiary;
    public Policy $policy;
    public MedicalAct $consultation;
    public MedicalAct $echo;

    /** Official tariffs used by the scenario, in FCFA. */
    public const CONSULTATION_TARIFF = 5000;
    public const ECHO_TARIFF = 10000;

    public static function create(int $coverageRate = 100): self
    {
        $s = new self();

        $s->facility = Organization::factory()->facility()->create();
        $s->otherFacility = Organization::factory()->facility()->create();
        $s->insurer = Organization::factory()->insurer()->create();
        $s->otherInsurer = Organization::factory()->insurer()->create();
        $s->facility->partnerInsurers()->attach($s->insurer);

        $s->agent = User::factory()->facilityAgent($s->facility)->create();
        $s->otherAgent = User::factory()->facilityAgent($s->otherFacility)->create();
        $s->manager = User::factory()->insurerManager($s->insurer)->create();
        $s->otherManager = User::factory()->insurerManager($s->otherInsurer)->create();
        $s->admin = User::factory()->platformAdmin()->create();

        $s->subscriber = Subscriber::factory()->create(['insurer_id' => $s->insurer->id]);
        $holder = Policyholder::factory()->create(['subscriber_id' => $s->subscriber->id]);
        $s->beneficiary = Beneficiary::factory()->create(['policyholder_id' => $holder->id]);
        $s->policy = Policy::factory()->rate($coverageRate)->create(['subscriber_id' => $s->subscriber->id]);

        $s->consultation = MedicalAct::factory()->create(['code' => 'CONSULT', 'label' => 'Consultation']);
        $s->echo = MedicalAct::factory()->create(['code' => 'ECHO', 'label' => 'Échographie']);
        OfficialTariff::factory()->create(['medical_act_id' => $s->consultation->id, 'amount' => self::CONSULTATION_TARIFF]);
        OfficialTariff::factory()->create(['medical_act_id' => $s->echo->id, 'amount' => self::ECHO_TARIFF]);

        return $s;
    }
}
