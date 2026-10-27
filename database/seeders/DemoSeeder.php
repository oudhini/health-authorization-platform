<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Beneficiary;
use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\Policyholder;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fictional demonstration data. Every name, number and amount is invented.
 * No real patient, policy or tariff is used here, ever.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = $this->demoPassword();

        // Two facilities and two insurers: enough to demonstrate that one
        // organization never sees another one's data.
        $facilityA = Organization::create(['name' => 'Hôpital Démo A', 'type' => OrganizationType::Facility]);
        $facilityB = Organization::create(['name' => 'Clinique Démo B', 'type' => OrganizationType::Facility]);
        $insurerA = Organization::create(['name' => 'Assurance Démo Alpha', 'type' => OrganizationType::Insurer]);
        $insurerB = Organization::create(['name' => 'Assurance Démo Beta', 'type' => OrganizationType::Insurer]);

        User::factory()->facilityAgent($facilityA)->create([
            'name' => 'Agent Démo A', 'email' => 'agent.a@demo.test', 'password' => $password,
        ]);
        User::factory()->facilityAgent($facilityB)->create([
            'name' => 'Agent Démo B', 'email' => 'agent.b@demo.test', 'password' => $password,
        ]);
        User::factory()->insurerManager($insurerA)->create([
            'name' => 'Gestionnaire Démo Alpha', 'email' => 'manager.alpha@demo.test', 'password' => $password,
        ]);
        User::factory()->insurerManager($insurerB)->create([
            'name' => 'Gestionnaire Démo Beta', 'email' => 'manager.beta@demo.test', 'password' => $password,
        ]);
        User::factory()->platformAdmin()->create([
            'name' => 'Administrateur Démo', 'email' => 'admin@demo.test', 'password' => $password,
        ]);

        $this->seedActsAndTariffs();
        $this->seedPolicies($insurerA, 'ALPHA', 100, 'Société Exemple 1');
        $this->seedPolicies($insurerB, 'BETA', 80, 'Société Exemple 2');
    }

    private function demoPassword(): string
    {
        $password = config('demo.password');

        if ($password !== null && $password !== '') {
            return (string) $password;
        }

        if (app()->environment(['local', 'testing'])) {
            return 'password';
        }

        throw new RuntimeException('Set DEMO_PASSWORD in .env before seeding demo accounts outside local and testing.');
    }

    private function seedActsAndTariffs(): void
    {
        $acts = [
            ['CONSULT-GEN', 'Consultation générale', 5000],
            ['ECHO-ABD', 'Échographie abdominale', 10000],
            ['RADIO-THO', 'Radiographie thoracique', 8000],
            ['LAB-NFS', 'Analyse : numération sanguine', 3500],
            ['LAB-GLY', 'Analyse : glycémie', 2000],
            ['SOINS-INF', 'Soins infirmiers', 1500],
        ];

        foreach ($acts as [$code, $label, $amount]) {
            $act = MedicalAct::create(['code' => $code, 'label' => $label]);
            OfficialTariff::create([
                'medical_act_id' => $act->id,
                'amount' => $amount,
                'effective_from' => '2026-01-01',
            ]);
        }
    }

    private function seedPolicies(Organization $insurer, string $tag, int $rate, string $subscriberName): void
    {
        $subscriber = Subscriber::create(['insurer_id' => $insurer->id, 'name' => $subscriberName]);

        Policy::create([
            'number' => "POL-{$tag}-0001",
            'insurer_id' => $insurer->id,
            'subscriber_id' => $subscriber->id,
            'coverage_rate' => $rate,
            // Relative dates: the demo policy is always active when the demo is seeded.
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYears(2)->toDateString(),
        ]);

        $holder = Policyholder::create(['subscriber_id' => $subscriber->id, 'name' => "Assuré Démo {$tag}"]);

        $members = [
            ['self', "Assuré Démo {$tag}", '1985-03-12'],
            ['child', "Enfant Démo {$tag} 1", '2015-06-01'],
            ['child', "Enfant Démo {$tag} 2", '2019-09-20'],
        ];

        foreach ($members as $index => [$relationship, $name, $birthDate]) {
            Beneficiary::create([
                'policyholder_id' => $holder->id,
                'insurer_id' => $insurer->id,
                'name' => $name,
                'birth_date' => $birthDate,
                'relationship' => $relationship,
                'member_number' => sprintf('DEMO-%s-%04d', $tag, $index + 1),
            ]);
        }
    }
}
