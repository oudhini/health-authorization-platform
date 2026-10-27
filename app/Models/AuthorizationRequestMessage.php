<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RequestMessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['authorization_request_id', 'user_id', 'type', 'body', 'document_count'])]
class AuthorizationRequestMessage extends Model
{
    protected function casts(): array
    {
        return [
            'type' => RequestMessageType::class,
            'document_count' => 'integer',
        ];
    }

    /** @return BelongsTo<AuthorizationRequest, $this> */
    public function authorizationRequest(): BelongsTo
    {
        return $this->belongsTo(AuthorizationRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
