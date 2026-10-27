<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMedicalActRequest;
use App\Http\Requests\Admin\StoreOfficialTariffRequest;
use App\Http\Requests\Admin\UpdateMedicalActRequest;
use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class MedicalCatalogController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-medical-catalog');

        return view('admin.medical-catalog.index', [
            'acts' => MedicalAct::query()
                ->with('tariffs')
                ->orderBy('label')
                ->paginate(20),
        ]);
    }

    public function store(StoreMedicalActRequest $request, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('manage-medical-catalog');
        $data = $request->validated();

        $act = DB::transaction(function () use ($data, $request, $audit): MedicalAct {
            $act = MedicalAct::query()->create([
                'code' => $data['code'],
                'label' => $data['label'],
            ]);
            $tariff = $act->tariffs()->create([
                'amount' => $data['amount'],
                'effective_from' => $data['effective_from'],
            ]);

            $audit->record(AuditAction::MedicalActCreated, $request->user(), $act);
            $audit->record(AuditAction::OfficialTariffCreated, $request->user(), $tariff, [
                'medical_act_id' => $act->id,
                'amount' => (int) $tariff->amount,
                'effective_from' => $tariff->effective_from->toDateString(),
            ]);

            return $act;
        });

        return redirect()->route('admin.medical-catalog.index')
            ->with('status', "L'acte {$act->code} et son tarif initial ont été ajoutés.");
    }

    public function update(
        UpdateMedicalActRequest $request,
        MedicalAct $medicalAct,
        AuditLogger $audit,
    ): RedirectResponse {
        Gate::authorize('manage-medical-catalog');
        $data = $request->validated();

        DB::transaction(function () use ($data, $medicalAct, $request, $audit): void {
            $oldActive = $medicalAct->is_active;
            $medicalAct->update([
                'code' => $data['code'],
                'label' => $data['label'],
                'is_active' => (bool) $data['is_active'],
            ]);

            $audit->record(AuditAction::MedicalActUpdated, $request->user(), $medicalAct, [
                'old_is_active' => $oldActive,
                'new_is_active' => $medicalAct->is_active,
            ]);
        });

        return redirect()->route('admin.medical-catalog.index')
            ->with('status', 'Le catalogue des actes a été mis à jour.');
    }

    public function storeTariff(
        StoreOfficialTariffRequest $request,
        MedicalAct $medicalAct,
        AuditLogger $audit,
    ): RedirectResponse {
        Gate::authorize('manage-medical-catalog');
        $data = $request->validated();

        if ($medicalAct->tariffs()->whereDate('effective_from', $data['effective_from'])->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'Un tarif existe déjà à cette date pour cet acte.',
            ]);
        }

        $tariff = DB::transaction(function () use ($data, $medicalAct, $request, $audit): OfficialTariff {
            $tariff = $medicalAct->tariffs()->create($data);
            $audit->record(AuditAction::OfficialTariffCreated, $request->user(), $tariff, [
                'medical_act_id' => $medicalAct->id,
                'amount' => (int) $tariff->amount,
                'effective_from' => $tariff->effective_from->toDateString(),
            ]);

            return $tariff;
        });

        return redirect()->route('admin.medical-catalog.index')
            ->with('status', "Le nouveau tarif sera applicable à partir du {$tariff->effective_from->format('d/m/Y')}.");
    }
}
