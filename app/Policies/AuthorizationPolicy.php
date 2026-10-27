<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Authorization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class AuthorizationPolicy
{
    public function __construct(private readonly AuthorizationRequestPolicy $requests)
    {
    }

    public function view(User $user, Authorization $authorization): Response
    {
        return $this->requests->view($user, $authorization->authorizationRequest);
    }
}
