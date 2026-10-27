<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $role = UserRole::tryFrom((string) $this->input('role'));
        $organizationType = $role?->requiredOrganizationType();
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'organization_id' => $organizationType === null
                ? ['nullable', 'prohibited_unless:role,platform_admin']
                : [
                    'required',
                    'integer',
                    Rule::exists('organizations', 'id')->where('type', $organizationType->value),
                ],
            'password' => [
                $user === null ? 'required' : 'nullable',
                'string',
                'min:12',
                'confirmed',
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
