<?php

namespace App\Modules\Auth\UseCases;

use App\Models\User;
use App\Modules\Auth\Mail\RecuperacaoSenhaMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class ForgotPasswordUseCase
{
    /**
     * Sempre retorna sucesso genérico para não revelar existência do e-mail.
     */
    public function execute(string $email): void
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $token = Password::broker()->createToken($user);

        Mail::to($user->email)->send(new RecuperacaoSenhaMail($user, $token));
    }
}
