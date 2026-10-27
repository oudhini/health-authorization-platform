@extends('layouts.app')

@section('title', 'Modifier le brouillon #'.$authorizationRequest->id)

@section('content')
    <h1 class="h2">Modifier le brouillon #{{ $authorizationRequest->id }}</h1>
    <p class="text-body-secondary">
        Bénéficiaire, assureur et police restent liés au brouillon. Les tarifs et montants sont recalculés à l’enregistrement.
        Un acte déjà utilisé dans cette demande reste sélectionnable même s’il a été désactivé.
    </p>

    @error('request')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('facility.requests.update', $authorizationRequest) }}" class="card shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body">
            <p><strong>Bénéficiaire :</strong> {{ $authorizationRequest->beneficiary->name }}</p>
            <p><strong>Assureur :</strong> {{ $authorizationRequest->insurer->name }}</p>
            <p><strong>Taux enregistré :</strong> {{ $authorizationRequest->coverage_rate }} %</p>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Acte médical</th>
                            <th scope="col">Montant facturé (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 10; $i++)
                            @php($item = $authorizationRequest->items->get($i))
                            <tr>
                                <td>
                                    <label class="visually-hidden" for="act_{{ $i }}">Acte {{ $i + 1 }}</label>
                                    <select class="form-select" id="act_{{ $i }}" name="acts[{{ $i }}][medical_act_id]">
                                        <option value="">— Aucun —</option>
                                        @foreach ($acts as $act)
                                            <option value="{{ $act->id }}"
                                                @selected((string) old("acts.$i.medical_act_id", $item?->medical_act_id) === (string) $act->id)>
                                                {{ $act->label }}{{ $act->is_active ? '' : ' (désactivé)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("acts.$i.medical_act_id")<div class="text-danger small">{{ $message }}</div>@enderror
                                </td>
                                <td>
                                    <label class="visually-hidden" for="amount_{{ $i }}">Montant facturé pour l’acte {{ $i + 1 }}</label>
                                    <input class="form-control" id="amount_{{ $i }}" name="acts[{{ $i }}][billed_amount]"
                                        type="number" min="1" step="1" inputmode="numeric"
                                        value="{{ old("acts.$i.billed_amount", $item?->billed_amount) }}">
                                    @error("acts.$i.billed_amount")<div class="text-danger small">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Enregistrer le brouillon</button>
                <a class="btn btn-outline-secondary" href="{{ route('facility.requests.show', $authorizationRequest) }}">Retour à la demande</a>
            </div>
        </div>
    </form>
@endsection
