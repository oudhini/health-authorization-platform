<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'authorization_request_id', 'type', 'original_name', 'stored_path',
    'mime_type', 'size_bytes', 'uploaded_by',
])]
class Document extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<AuthorizationRequest, $this> */
    public function authorizationRequest(): BelongsTo
    {
        return $this->belongsTo(AuthorizationRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
