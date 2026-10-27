<?php

declare(strict_types=1);

namespace App\Http\Requests\CoverageRoster;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ImportCoverageRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::InsurerManager, UserRole::PlatformAdmin], true);
    }

    /** @return array<string, list<\Illuminate\Validation\Rules\RequiredIf|string>> */
    public function rules(): array
    {
        return [
            'insurer_id' => [Rule::requiredIf($this->user()?->role === UserRole::PlatformAdmin), 'integer', 'exists:organizations,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'fingerprint' => [$this->routeIs('coverage-roster.import') ? 'required' : 'nullable', 'regex:/^[a-f0-9]{64}$/'],
        ];
    }
}
