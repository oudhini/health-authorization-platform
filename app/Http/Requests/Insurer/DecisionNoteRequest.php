<?php

declare(strict_types=1);

namespace App\Http\Requests\Insurer;

use Illuminate\Foundation\Http\FormRequest;

final class DecisionNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['note' => ['required', 'string', 'min:5', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'note.required' => 'Le message est obligatoire.',
            'note.min' => 'Le message doit contenir au moins 5 caractères.',
            'note.max' => 'Le message ne doit pas dépasser 1000 caractères.',
        ];
    }
}
