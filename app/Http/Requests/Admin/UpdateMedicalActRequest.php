<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MedicalAct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMedicalActRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-medical-catalog') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var MedicalAct $medicalAct */
        $medicalAct = $this->route('medicalAct');

        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('medical_acts', 'code')->ignore($medicalAct)],
            'label' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
