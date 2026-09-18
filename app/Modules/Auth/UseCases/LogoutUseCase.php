<?php

namespace App\Modules\Auth\UseCases;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUseCase
{
    public function execute(User $user): void
    {
        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
