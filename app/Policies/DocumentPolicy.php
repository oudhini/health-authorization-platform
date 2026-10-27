<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/** A document follows the visibility rules of the request it belongs to. */
final class DocumentPolicy
{
    public function __construct(private readonly AuthorizationRequestPolicy $requests)
    {
    }

    public function view(User $user, Document $document): Response
    {
        return $this->requests->view($user, $document->authorizationRequest);
    }
}
