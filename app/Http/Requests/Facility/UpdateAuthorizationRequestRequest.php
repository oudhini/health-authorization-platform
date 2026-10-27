<?php

declare(strict_types=1);

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAuthorizationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('acts', []))
            ->filter(fn (mixed $row): bool => is_array($row)
                && (($row['medical_act_id'] ?? '') !== '' || ($row['billed_amount'] ?? '') !== ''))
            ->values()
            ->all();

        $this->merge(['acts' => $rows]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'acts' => ['required', 'array', 'min:1', 'max:10'],
            'acts.*.medical_act_id' => ['required', 'integer', 'exists:medical_acts,id'],
            'acts.*.billed_amount' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    /** @return list<array{medical_act_id: int, billed_amount: int}> */
    public function acts(): array
    {
        return array_map(
            static fn (array $row): array => [
                'medical_act_id' => (int) $row['medical_act_id'],
                'billed_amount' => (int) $row['billed_amount'],
            ],
            $this->validated('acts'),
        );
    }
}
