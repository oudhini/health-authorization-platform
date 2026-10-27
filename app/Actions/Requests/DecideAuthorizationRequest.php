<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use App\Enums\AuditAction;
use App\Enums\RequestMessageType;
use App\Models\Authorization;
use App\Models\AuthorizationRequest;
use App\Models\AuthorizationRequestMessage;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Applies the insurer's decision and creates a frozen authorization on approval.
 */
final class DecideAuthorizationRequest
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @throws InvalidStatusTransition */
    public function approve(User $manager, AuthorizationRequest $request): Authorization
    {
        return DB::transaction(function () use ($manager, $request): Authorization {
            $locked = $this->lock($request);
            $locked->status = $locked->status->transitionTo(RequestStatus::Approved);
            $locked->decided_by = $manager->id;
            $locked->decided_at = now();
            $locked->decision_note = null;
            $locked->save();

            $totals = $locked->coverageTotals();
            $validFrom = CarbonImmutable::today();
            $authorization = Authorization::create([
                'authorization_request_id' => $locked->id,
                'valid_from' => $validFrom->toDateString(),
                'valid_until' => $validFrom->addDays((int) config('authorization.validity_days'))->toDateString(),
                'billed_total' => $totals->billedAmount,
                'insurer_total' => $totals->insurerShare,
                'patient_total' => $totals->patientShare,
                'overrun_total' => $totals->overrun,
                'decided_by' => $manager->id,
            ]);

            $this->audit->record(AuditAction::RequestApproved, $manager, $locked);
            $this->audit->record(AuditAction::AuthorizationGenerated, $manager, $authorization);

            return $authorization;
        });
    }

    /** @throws InvalidStatusTransition */
    public function reject(User $manager, AuthorizationRequest $request, string $reason): AuthorizationRequest
    {
        return $this->decide($manager, $request, RequestStatus::Rejected, $reason, AuditAction::RequestRejected);
    }

    /** @throws InvalidStatusTransition */
    public function requestInformation(User $manager, AuthorizationRequest $request, string $message): AuthorizationRequest
    {
        return $this->decide(
            $manager,
            $request,
            RequestStatus::InfoRequested,
            $message,
            AuditAction::RequestInfoRequested,
        );
    }

    private function decide(
        User $manager,
        AuthorizationRequest $request,
        RequestStatus $target,
        string $note,
        AuditAction $action,
    ): AuthorizationRequest {
        return DB::transaction(function () use ($manager, $request, $target, $note, $action): AuthorizationRequest {
            $locked = $this->lock($request);
            $locked->status = $locked->status->transitionTo($target);
            $locked->decided_by = $manager->id;
            $locked->decided_at = now();
            $locked->decision_note = $note;
            $locked->save();

            if ($target === RequestStatus::InfoRequested) {
                AuthorizationRequestMessage::create([
                    'authorization_request_id' => $locked->id,
                    'user_id' => $manager->id,
                    'type' => RequestMessageType::InformationRequested,
                    'body' => $note,
                    'document_count' => $locked->documents()->count(),
                ]);
            }

            // Decision messages can contain medical wording, so they stay out of audit metadata.
            $this->audit->record($action, $manager, $locked);

            return $locked;
        });
    }

    private function lock(AuthorizationRequest $request): AuthorizationRequest
    {
        return AuthorizationRequest::query()
            ->whereKey($request->getKey())
            ->lockForUpdate()
            ->with('items')
            ->firstOrFail();
    }
}
