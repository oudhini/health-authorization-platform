<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOfficialTariffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-medical-catalog') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'effective_from' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }
}
