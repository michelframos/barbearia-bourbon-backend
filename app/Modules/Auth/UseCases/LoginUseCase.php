<?php

namespace App\Modules\Auth\UseCases;

use App\Models\User;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUseCase
{
    /**
     * @return array{user: User, token: string}
     */
    public function execute(string $email, string $password, string $deviceName = 'api'): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if ((int) $user->id_empresa !== EmpresaContext::id()) {
            throw ValidationException::withMessages([
                'email' => ['Usuário não pertence à empresa da instalação.'],
            ]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
