<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrganizationType;
use App\Models\Beneficiary;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\Policyholder;
use App\Models\Subscriber;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class CoverageRosterCsv
{
    private const HEADERS = [
        'subscriber_name',
        'policyholder_name',
        'beneficiary_name',
        'birth_date',
        'relationship',
        'member_number',
        'policy_number',
        'coverage_rate',
        'starts_on',
        'ends_on',
    ];

    private const MAX_ROWS = 500;

    public function fingerprint(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Le fichier CSV n’a pas pu être lu.');
        }

        return hash('sha256', $contents);
    }

    /**
     * @return array{rows: list<array<string, string|int>>, errors: list<string>, creates: int, updates: int}
     */
    public function preview(UploadedFile $file, int $insurerId): array
    {
        $insurer = Organization::query()
            ->whereKey($insurerId)
            ->where('type', OrganizationType::Insurer)
            ->first();

        if ($insurer === null) {
            throw ValidationException::withMessages(['insurer_id' => 'Sélectionnez une organisation de type assureur.']);
        }

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('Le fichier CSV n’a pas pu être lu.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                return ['rows' => [], 'errors' => ['Le fichier CSV est vide.'], 'creates' => 0, 'updates' => 0];
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
            $headers = array_map(static fn ($value): string => trim((string) $value), $headers);
            if ($headers !== self::HEADERS) {
                return [
                    'rows' => [],
                    'errors' => ['Les en-têtes ne correspondent pas au modèle CSV. Téléchargez le modèle et respectez l’ordre des colonnes.'],
                    'creates' => 0,
                    'updates' => 0,
                ];
            }

            $rows = [];
            $errors = [];
            $seenMembers = [];
            $seenPolicies = [];
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                    continue;
                }
                if (count($rows) >= self::MAX_ROWS) {
                    $errors[] = 'Le fichier dépasse la limite de '.self::MAX_ROWS.' lignes de données.';
                    break;
                }

                if (count($values) !== count(self::HEADERS)) {
                    $errors[] = "Ligne {$line} : le nombre de colonnes est incorrect.";
                    continue;
                }

                $row = array_combine(self::HEADERS, array_map(
                    static fn ($value): string => trim((string) $value),
                    $values,
                ));
                if ($row === false) {
                    $errors[] = "Ligne {$line} : ligne CSV invalide.";
                    continue;
                }

                $validator = Validator::make($row, [
                    'subscriber_name' => ['required', 'string', 'max:255'],
                    'policyholder_name' => ['required', 'string', 'max:255'],
                    'beneficiary_name' => ['required', 'string', 'max:255'],
                    'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                    'relationship' => ['required', 'in:self,spouse,child,other'],
                    'member_number' => ['required', 'string', 'max:50'],
                    'policy_number' => ['required', 'string', 'max:50'],
                    'coverage_rate' => ['required', 'integer', 'between:0,100'],
                    'starts_on' => ['required', 'date_format:Y-m-d'],
                    'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
                ]);
                if ($validator->fails()) {
                    $errors[] = "Ligne {$line} : ".$validator->errors()->first();
                    continue;
                }

                if (isset($seenMembers[$row['member_number']])) {
                    $errors[] = "Ligne {$line} : le numéro bénéficiaire apparaît plusieurs fois dans le fichier.";
                    continue;
                }
                $seenMembers[$row['member_number']] = $line;

                $policySignature = implode('|', [
                    $row['subscriber_name'],
                    $row['policy_number'],
                    $row['coverage_rate'],
                    $row['starts_on'],
                    $row['ends_on'],
                ]);
                if (isset($seenPolicies[$row['policy_number']]) && $seenPolicies[$row['policy_number']] !== $policySignature) {
                    $errors[] = "Ligne {$line} : le même numéro de contrat contient des informations contradictoires.";
                    continue;
                }
                $seenPolicies[$row['policy_number']] = $policySignature;
                $rows[] = $row;
            }

            foreach ($rows as $index => $row) {
                $existing = Beneficiary::query()
                    ->where('insurer_id', $insurer->id)
                    ->where('member_number', $row['member_number'])
                    ->with('policyholder.subscriber')
                    ->first();

                $policy = Policy::query()
                    ->where('insurer_id', $insurer->id)
                    ->where('number', $row['policy_number'])
                    ->first();
                if ($policy !== null) {
                    $policySubscriber = Subscriber::query()->find($policy->subscriber_id);
                    if ($policySubscriber === null || $policySubscriber->name !== $row['subscriber_name']) {
                        $errors[] = 'Ligne '.($index + 2).' : ce numéro de contrat est déjà rattaché à un autre souscripteur.';
                    }
                }
            }

            $creates = 0;
            $updates = 0;
            foreach ($rows as $row) {
                if (Beneficiary::query()
                    ->where('insurer_id', $insurer->id)
                    ->where('member_number', $row['member_number'])
                    ->exists()) {
                    $updates++;
                } else {
                    $creates++;
                }
            }

            return compact('rows', 'errors', 'creates', 'updates');
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param list<array<string, string|int>> $rows
     */
    public function import(array $rows, int $insurerId, User $user, AuditLogger $audit): int
    {
        return DB::transaction(function () use ($rows, $insurerId, $user, $audit): int {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $subscriber = Subscriber::query()->firstOrCreate(
                    ['insurer_id' => $insurerId, 'name' => $row['subscriber_name']],
                );
                $policyholder = Policyholder::query()->firstOrCreate(
                    ['subscriber_id' => $subscriber->id, 'name' => $row['policyholder_name']],
                );

                $beneficiary = Beneficiary::query()
                    ->where('insurer_id', $insurerId)
                    ->where('member_number', $row['member_number'])
                    ->first();
                if ($beneficiary === null) {
                    $beneficiary = new Beneficiary();
                    $created++;
                } else {
                    $updated++;
                }
                $beneficiary->fill([
                    'policyholder_id' => $policyholder->id,
                    'insurer_id' => $insurerId,
                    'name' => $row['beneficiary_name'],
                    'birth_date' => $row['birth_date'],
                    'relationship' => $row['relationship'],
                    'member_number' => $row['member_number'],
                ])->save();

                $policy = Policy::query()
                    ->where('insurer_id', $insurerId)
                    ->where('number', $row['policy_number'])
                    ->first();
                if ($policy !== null && (int) $policy->subscriber_id !== (int) $subscriber->id) {
                    throw ValidationException::withMessages(['csv_file' => 'Un contrat ne peut pas être déplacé vers un autre souscripteur.']);
                }
                $policy ??= new Policy();
                $policy->fill([
                    'number' => $row['policy_number'],
                    'insurer_id' => $insurerId,
                    'subscriber_id' => $subscriber->id,
                    'coverage_rate' => (int) $row['coverage_rate'],
                    'starts_on' => $row['starts_on'],
                    'ends_on' => $row['ends_on'],
                ])->save();
            }

            $audit->record(AuditAction::CoverageRosterImported, $user, null, [
                'insurer_id' => $insurerId,
                'created' => $created,
                'updated' => $updated,
            ]);

            return count($rows);
        });
    }
}
