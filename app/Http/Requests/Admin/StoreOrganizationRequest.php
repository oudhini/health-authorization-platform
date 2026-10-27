<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('organizations', 'name')],
            'type' => ['required', Rule::enum(OrganizationType::class)],
        ];
    }
}
