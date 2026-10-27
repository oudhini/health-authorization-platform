<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Http\Requests\CoverageRoster\ImportCoverageRosterRequest;
use App\Http\Requests\CoverageRoster\StoreCoverageRecordRequest;
use App\Models\Beneficiary;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\Policyholder;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CoverageRosterCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CoverageRosterController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-coverage-roster');
        $insurer = $this->resolveInsurer($request, $request->query('insurer_id'));
        $beneficiaries = $insurer === null
            ? Beneficiary::query()->whereRaw('1 = 0')->paginate(25)
            : Beneficiary::query()
                ->whereHas('policyholder.subscriber', fn ($query) => $query->where('insurer_id', $insurer->id))
                ->with('policyholder.subscriber.policies')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString();

        return view('coverage-roster.index', [
            'insurer' => $insurer,
            'insurers' => $this->insurersFor($request->user()),
            'beneficiaries' => $beneficiaries,
        ]);
    }

    public function template()
    {
        Gate::authorize('manage-coverage-roster');
        $headers = ['subscriber_name', 'policyholder_name', 'beneficiary_name', 'birth_date', 'relationship', 'member_number', 'policy_number', 'coverage_rate', 'starts_on', 'ends_on'];
        $sample = ['Entreprise Démo', 'Assuré Démo', 'Bénéficiaire Démo', '1990-01-15', 'self', 'DEMO-0001', 'POL-DEMO-0001', '80', '2026-01-01', '2026-12-31'];

        return response()->streamDownload(static function () use ($headers, $sample): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            fputcsv($output, $sample);
            fclose($output);
        }, 'modele-portefeuille-assure.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-coverage-roster');

        return view('coverage-roster.form', [
            'insurer' => $this->resolveInsurer($request, $request->query('insurer_id')),
            'insurers' => $this->insurersFor($request->user()),
            'beneficiary' => null,
            'record' => [],
        ]);
    }

    public function store(
        StoreCoverageRecordRequest $request,
        AuditLogger $audit,
    ): RedirectResponse {
        $insurer = $this->resolveInsurer($request, $request->validated('insurer_id'));
        $data = $request->validated();

        DB::transaction(function () use ($data, $insurer, $request, $audit): void {
            $subscriber = Subscriber::query()->firstOrCreate([
                'insurer_id' => $insurer->id,
                'name' => $data['subscriber_name'],
            ]);
            $policyholder = Policyholder::query()->firstOrCreate([
                'subscriber_id' => $subscriber->id,
                'name' => $data['policyholder_name'],
            ]);
            $beneficiary = Beneficiary::query()->create([
                'policyholder_id' => $policyholder->id,
                'insurer_id' => $insurer->id,
                'name' => $data['beneficiary_name'],
                'birth_date' => $data['birth_date'],
                'relationship' => $data['relationship'],
                'member_number' => $data['member_number'],
            ]);

            $policy = Policy::query()
                ->where('insurer_id', $insurer->id)
                ->where('number', $data['policy_number'])
                ->first();
            if ($policy !== null && (int) $policy->subscriber_id !== (int) $subscriber->id) {
                throw ValidationException::withMessages(['policy_number' => 'Ce contrat est rattaché à un autre souscripteur de cet assureur.']);
            }
            if ($policy === null) {
                Policy::query()->create([
                    'number' => $data['policy_number'],
                    'insurer_id' => $insurer->id,
                    'subscriber_id' => $subscriber->id,
                    'coverage_rate' => $data['coverage_rate'],
                    'starts_on' => $data['starts_on'],
                    'ends_on' => $data['ends_on'],
                ]);
            } elseif ($policy->coverage_rate !== (int) $data['coverage_rate']
                || $policy->starts_on->toDateString() !== $data['starts_on']
                || $policy->ends_on->toDateString() !== $data['ends_on']) {
                throw ValidationException::withMessages([
                    'policy_number' => 'Ce contrat existe déjà avec des paramètres différents; utilisez les paramètres du contrat enregistré.',
                ]);
            }
            $audit->record(AuditAction::CoverageRecordCreated, $request->user(), $beneficiary, [
                'insurer_id' => $insurer->id,
            ]);
        });

        return redirect()->route('coverage-roster.index', ['insurer_id' => $insurer->id])
            ->with('status', 'Le dossier assuré a été créé.');
    }

    public function edit(Request $request, Beneficiary $beneficiary): View
    {
        Gate::authorize('manage-coverage-roster');
        $insurer = $this->insurerForBeneficiary($beneficiary);
        $this->assertInsurerAccess($request->user(), $insurer);
        $beneficiary->load('policyholder.subscriber.policies');
        $subscriber = $beneficiary->policyholder->subscriber;
        $policy = $subscriber->policies
            ->where('insurer_id', $insurer->id)
            ->sortByDesc('starts_on')
            ->first();

        abort_if($policy === null, 404);

        return view('coverage-roster.form', [
            'insurer' => $insurer,
            'insurers' => $this->insurersFor($request->user()),
            'beneficiary' => $beneficiary,
            'record' => [
                'subscriber_name' => $subscriber->name,
                'policyholder_name' => $beneficiary->policyholder->name,
                'beneficiary_name' => $beneficiary->name,
                'birth_date' => $beneficiary->birth_date->toDateString(),
                'relationship' => $beneficiary->relationship,
                'member_number' => $beneficiary->member_number,
                'policy_number' => $policy->number,
                'policy_id' => $policy->id,
                'coverage_rate' => $policy->coverage_rate,
                'starts_on' => $policy->starts_on->toDateString(),
                'ends_on' => $policy->ends_on->toDateString(),
            ],
        ]);
    }

    public function update(
        StoreCoverageRecordRequest $request,
        Beneficiary $beneficiary,
        AuditLogger $audit,
    ): RedirectResponse {
        $insurer = $this->insurerForBeneficiary($beneficiary);
        $this->assertInsurerAccess($request->user(), $insurer);
        abort_if($request->validated('insurer_id') !== null
            && (int) $request->validated('insurer_id') !== (int) $insurer->id, 404);
        $data = $request->validated();
        $subscriber = $beneficiary->policyholder()->with('subscriber')->firstOrFail()->subscriber;
        $policyholder = $beneficiary->policyholder;

        DB::transaction(function () use ($data, $insurer, $beneficiary, $subscriber, $policyholder, $request, $audit): void {
            $policy = Policy::query()
                ->whereKey($data['policy_id'] ?? null)
                ->where('insurer_id', $insurer->id)
                ->where('subscriber_id', $subscriber->id)
                ->first();
            $policyWithNumber = Policy::query()
                ->where('insurer_id', $insurer->id)
                ->where('number', $data['policy_number'])
                ->first();
            if ($policyWithNumber !== null && (int) $policyWithNumber->id !== (int) $policy?->id) {
                throw ValidationException::withMessages(['policy_number' => 'Ce numéro de contrat appartient à un autre dossier.']);
            }

            $subscriber->update(['name' => $data['subscriber_name']]);
            $policyholder->update(['name' => $data['policyholder_name']]);
            $beneficiary->update([
                'name' => $data['beneficiary_name'],
                'birth_date' => $data['birth_date'],
                'relationship' => $data['relationship'],
                'member_number' => $data['member_number'],
            ]);
            if ($policy === null) {
                throw ValidationException::withMessages(['policy_number' => 'Le contrat à corriger est introuvable.']);
            }
            $policy->update([
                'number' => $data['policy_number'],
                'coverage_rate' => $data['coverage_rate'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
            ]);
            $audit->record(AuditAction::CoverageRecordUpdated, $request->user(), $beneficiary, [
                'insurer_id' => $insurer->id,
            ]);
        });

        return redirect()->route('coverage-roster.index', ['insurer_id' => $insurer->id])
            ->with('status', 'Le dossier assuré a été mis à jour.');
    }

    public function previewImport(ImportCoverageRosterRequest $request, CoverageRosterCsv $csv): View|RedirectResponse
    {
        $insurer = $this->resolveInsurer($request, $request->validated('insurer_id'));
        $preview = $csv->preview($request->file('csv_file'), $insurer->id);

        return view('coverage-roster.import-preview', [
            'insurer' => $insurer,
            'preview' => $preview,
            'hasFileErrors' => $preview['errors'] !== [],
            'fingerprint' => hash_hmac('sha256', $csv->fingerprint($request->file('csv_file')), (string) config('app.key')),
        ]);
    }

    public function import(
        ImportCoverageRosterRequest $request,
        CoverageRosterCsv $csv,
        AuditLogger $audit,
    ): RedirectResponse {
        $insurer = $this->resolveInsurer($request, $request->validated('insurer_id'));
        $expectedFingerprint = hash_hmac('sha256', $csv->fingerprint($request->file('csv_file')), (string) config('app.key'));
        if (! hash_equals($request->validated('fingerprint'), $expectedFingerprint)) {
            throw ValidationException::withMessages(['csv_file' => 'Le fichier sélectionné est différent de celui vérifié. Relancez l’aperçu avant de confirmer.']);
        }
        $preview = $csv->preview($request->file('csv_file'), $insurer->id);
        if ($preview['errors'] !== []) {
            throw ValidationException::withMessages(['csv_file' => implode(' ', $preview['errors'])]);
        }
        $count = $csv->import($preview['rows'], $insurer->id, $request->user(), $audit);

        return redirect()->route('coverage-roster.index', ['insurer_id' => $insurer->id])
            ->with('status', "{$count} ligne(s) du portefeuille ont été importées.");
    }

    private function resolveInsurer(Request $request, mixed $requestedId = null): ?Organization
    {
        $user = $request->user();
        if ($user->role === UserRole::InsurerManager) {
            $insurer = Organization::query()
                ->whereKey($user->organization_id)
                ->where('type', OrganizationType::Insurer)
                ->firstOrFail();
            if ($requestedId !== null && (int) $requestedId !== (int) $insurer->id) {
                abort(404);
            }

            return $insurer;
        }
        if ($requestedId === null || $requestedId === '') {
            return null;
        }

        return Organization::query()
            ->whereKey($requestedId)
            ->where('type', OrganizationType::Insurer)
            ->firstOrFail();
    }

    private function insurerForBeneficiary(Beneficiary $beneficiary): Organization
    {
        return $beneficiary->policyholder()
            ->with('subscriber')
            ->firstOrFail()
            ->subscriber
            ->insurer()
            ->where('type', OrganizationType::Insurer)
            ->firstOrFail();
    }

    private function assertInsurerAccess(User $user, Organization $insurer): void
    {
        abort_unless($user->role === UserRole::PlatformAdmin
            || (int) $user->organization_id === (int) $insurer->id, 404);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Organization> */
    private function insurersFor(User $user)
    {
        if ($user->role === UserRole::InsurerManager) {
            return Organization::query()->whereKey($user->organization_id)->get();
        }

        return Organization::query()->where('type', OrganizationType::Insurer)->orderBy('name')->get();
    }
}
