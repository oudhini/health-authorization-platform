<?php

declare(strict_types=1);

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAuthorizationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is done by the policy in the controller.
        return true;
    }

    /** The form shows several empty rows: rows with no value at all are dropped. */
    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('acts', []))
            ->filter(fn (mixed $row): bool => is_array($row)
                && (($row['medical_act_id'] ?? '') !== '' || ($row['billed_amount'] ?? '') !== ''))
            ->values()
            ->all();

        $this->merge(['acts' => $rows]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'insurer_id' => ['required', 'integer'],
            'coverage_selection' => ['required', 'string', 'regex:/^\d+:\d+$/'],
            'related_request_id' => ['nullable', 'integer'],
            'acts' => ['required', 'array', 'min:1', 'max:10'],
            'acts.*.medical_act_id' => ['required', 'integer', 'exists:medical_acts,id'],
            'acts.*.billed_amount' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'insurer_id.required' => 'Choisissez un assureur partenaire.',
            'coverage_selection.required' => 'Sélectionnez un bénéficiaire et son contrat.',
            'coverage_selection.regex' => 'Sélectionnez une couverture dans les résultats de recherche.',
            'acts.required' => 'Ajoutez au moins un acte médical.',
            'acts.min' => 'Ajoutez au moins un acte médical.',
            'acts.*.medical_act_id.required' => 'Choisissez l\'acte de chaque ligne renseignée.',
            'acts.*.medical_act_id.exists' => 'Un des actes choisis n\'existe pas.',
            'acts.*.billed_amount.required' => 'Indiquez le montant facturé de chaque ligne renseignée.',
            'acts.*.billed_amount.integer' => 'Le montant facturé doit être un nombre entier (en FCFA).',
            'acts.*.billed_amount.min' => 'Le montant facturé doit être supérieur à zéro.',
        ];
    }

    /** @return array{insurer_id: int, beneficiary_id: int, policy_id: int} */
    public function coverageSelection(): array
    {
        [$beneficiaryId, $policyId] = array_map('intval', explode(':', $this->validated('coverage_selection')));

        return [
            'insurer_id' => (int) $this->validated('insurer_id'),
            'beneficiary_id' => $beneficiaryId,
            'policy_id' => $policyId,
        ];
    }

    /** @return list<array{medical_act_id: int, billed_amount: int}> */
    public function acts(): array
    {
        return array_map(
            fn (array $row): array => [
                'medical_act_id' => (int) $row['medical_act_id'],
                'billed_amount' => (int) $row['billed_amount'],
            ],
            $this->validated('acts'),
        );
    }

    public function relatedRequestId(): ?int
    {
        $value = $this->validated('related_request_id');

        return $value === null || $value === '' ? null : (int) $value;
    }
}
