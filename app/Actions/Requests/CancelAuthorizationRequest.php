<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use App\Enums\AuditAction;
use App\Models\AuthorizationRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/** draft or info_requested to cancelled. */
final class CancelAuthorizationRequest
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @throws InvalidStatusTransition */
    public function handle(User $agent, AuthorizationRequest $request): AuthorizationRequest
    {
        return DB::transaction(function () use ($agent, $request): AuthorizationRequest {
            $locked = AuthorizationRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            $locked->status = $locked->status->transitionTo(RequestStatus::Cancelled);
            $locked->save();

            $this->audit->record(AuditAction::RequestCancelled, $agent, $locked);

            return $locked;
        });
    }
}
