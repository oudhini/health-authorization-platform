<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single entry point to write the audit trail (docs/requirements.md, 8.3).
 *
 * Never pass medical details in $metadata: only identifiers and technical facts.
 */
final class AuditLogger
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function record(
        AuditAction $action,
        ?User $user = null,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
