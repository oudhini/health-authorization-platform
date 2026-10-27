<?php

declare(strict_types=1);

namespace App\Http\Requests\CoverageRoster;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCoverageRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::InsurerManager, UserRole::PlatformAdmin], true);
    }

    /** @return array<string, list<\Illuminate\Validation\Rules\Unique|string>> */
    public function rules(): array
    {
        return [
            'insurer_id' => [Rule::requiredIf($this->user()?->role === UserRole::PlatformAdmin), 'integer', 'exists:organizations,id'],
            'subscriber_name' => ['required', 'string', 'max:255'],
            'policyholder_name' => ['required', 'string', 'max:255'],
            'beneficiary_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'relationship' => ['required', Rule::in(['self', 'spouse', 'child', 'other'])],
            'member_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('beneficiaries', 'member_number')
                    ->where('insurer_id', $this->input('insurer_id', $this->user()?->organization_id))
                    ->ignore($this->route('beneficiary')),
            ],
            'policy_number' => ['required', 'string', 'max:50'],
            'policy_id' => ['nullable', 'integer'],
            'coverage_rate' => ['required', 'integer', 'between:0,100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'member_number.unique' => 'Ce numéro de bénéficiaire est déjà utilisé.',
            'coverage_rate.between' => 'Le taux doit être compris entre 0 et 100 %.',
            'ends_on.after_or_equal' => 'La fin du contrat doit être postérieure ou égale à son début.',
        ];
    }
}
