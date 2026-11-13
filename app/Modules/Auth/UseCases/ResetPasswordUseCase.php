<?php

namespace App\Modules\Auth\UseCases;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordUseCase
{
    public function execute(string $email, string $token, string $password): void
    {
        $status = Password::broker()->reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $password,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return;
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Este link de recuperação é inválido ou já foi utilizado.',
            Password::INVALID_USER => 'Não foi possível redefinir a senha para este e-mail.',
            default => 'Não foi possível redefinir a senha. Solicite um novo link.',
        };

        // Token expirado usa o mesmo código INVALID_TOKEN no broker Laravel.
        if ($status === Password::INVALID_TOKEN) {
            $message = 'Este link de recuperação é inválido, expirou ou já foi utilizado.';
        }

        throw ValidationException::withMessages([
            'token' => [$message],
        ]);
    }
}
