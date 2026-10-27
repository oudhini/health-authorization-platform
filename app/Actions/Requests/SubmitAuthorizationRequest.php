<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Domain\Requests\Exceptions\CannotSubmitRequest;
use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use App\Enums\AuditAction;
use App\Enums\RequestMessageType;
use App\Models\AuthorizationRequest;
use App\Models\AuthorizationRequestMessage;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** draft or info_requested to submitted, under the conditions of section 5.2. */
final class SubmitAuthorizationRequest
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @throws InvalidStatusTransition when the current status forbids submitting
     * @throws CannotSubmitRequest when a condition of section 5.2 is not met
     */
    public function handle(
        User $agent,
        AuthorizationRequest $request,
        ?string $responseMessage = null,
    ): AuthorizationRequest {
        return DB::transaction(function () use ($agent, $request, $responseMessage): AuthorizationRequest {
            // Lock the row so two simultaneous submissions cannot both succeed.
            $locked = AuthorizationRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            $newStatus = $locked->status->transitionTo(RequestStatus::Submitted);

            $locked->load(['items', 'documents', 'policy']);
            $problems = [];

            if ($locked->status === RequestStatus::InfoRequested) {
                $latestInformationRequest = $locked->messages()
                    ->where('type', RequestMessageType::InformationRequested)
                    ->latest('id')
                    ->first();
                $newDocumentUploaded = $latestInformationRequest !== null
                    && $locked->documents->count() > (int) $latestInformationRequest->document_count;
                $responseMessage = is_string($responseMessage) ? trim($responseMessage) : null;

                if (! $newDocumentUploaded && ($responseMessage === null || $responseMessage === '')) {
                    $problems[] = 'Ajoutez une réponse au gestionnaire ou un nouveau document avant de renvoyer la demande.';
                }

                if ($responseMessage !== null && $responseMessage !== '') {
                    AuthorizationRequestMessage::create([
                        'authorization_request_id' => $locked->id,
                        'user_id' => $agent->id,
                        'type' => RequestMessageType::FacilityResponse,
                        'body' => $responseMessage,
                    ]);
                } elseif ($newDocumentUploaded) {
                    AuthorizationRequestMessage::create([
                        'authorization_request_id' => $locked->id,
                        'user_id' => $agent->id,
                        'type' => RequestMessageType::FacilityResponse,
                        'body' => 'Documents complémentaires joints à la demande.',
                    ]);
                }

                if ($responseMessage !== null || $newDocumentUploaded) {
                    $this->audit->record(AuditAction::RequestResponseSubmitted, $agent, $locked);
                }
            }

            if ($locked->items->isEmpty()) {
                $problems[] = 'La demande ne contient aucun acte.';
            }

            if (! $locked->policy->isActiveOn(CarbonImmutable::today())) {
                $problems[] = 'La police du bénéficiaire n\'est plus active.';
            }

            foreach ($locked->missingDocumentTypes() as $type) {
                $problems[] = "Document obligatoire manquant : {$type->label()}.";
            }

            if ($problems !== []) {
                throw CannotSubmitRequest::because($problems);
            }

            $locked->status = $newStatus;
            $locked->submitted_at = now();
            $locked->save();

            $this->audit->record(AuditAction::RequestSubmitted, $agent, $locked);

            return $locked;
        });
    }
}
