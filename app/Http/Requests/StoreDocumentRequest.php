<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DocumentType::class)],
            // mimes: checked from the file CONTENT, not only its extension. Max 5 MB (section 8.2).
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.required' => 'Choisissez le type de document.',
            'document.required' => 'Choisissez un fichier.',
            'document.mimes' => 'Formats acceptés : PDF, JPEG ou PNG.',
            'document.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
        ];
    }
}
