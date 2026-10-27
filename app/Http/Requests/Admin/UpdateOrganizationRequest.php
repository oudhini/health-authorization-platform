<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('organizations', 'name')->ignore($organization)],
        ];
    }
}
