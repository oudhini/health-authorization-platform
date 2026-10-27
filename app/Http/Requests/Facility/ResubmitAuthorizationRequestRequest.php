<?php

declare(strict_types=1);

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

final class ResubmitAuthorizationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('response'))) {
            $response = trim($this->input('response'));
            $this->merge(['response' => $response === '' ? null : $response]);
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['response' => ['nullable', 'string', 'max:1000']];
    }

    public function responseMessage(): ?string
    {
        return $this->validated('response');
    }
}
