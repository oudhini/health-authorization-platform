<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreMedicalActRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-medical-catalog');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('medical_acts', 'code')],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'effective_from' => ['required', 'date', 'after_or_equal:today', Rule::unique('official_tariffs', 'effective_from')->where('medical_act_id', $this->route('medicalAct')?->id)],
        ];
    }
}
