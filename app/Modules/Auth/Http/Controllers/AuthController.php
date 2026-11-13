<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\UseCases\ForgotPasswordUseCase;
use App\Modules\Auth\UseCases\LoginUseCase;
use App\Modules\Auth\UseCases\LogoutUseCase;
use App\Modules\Auth\UseCases\ResetPasswordUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LogoutUseCase $logoutUseCase,
        private readonly ForgotPasswordUseCase $forgotPasswordUseCase,
        private readonly ResetPasswordUseCase $resetPasswordUseCase,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginUseCase->execute(
            email: $request->validated('email'),
            password: $request->validated('password'),
            deviceName: $request->validated('device_name', 'api'),
        );

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'data' => new UserResource($result['user']),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutUseCase->execute($request->user());

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->forgotPasswordUseCase->execute($request->validated('email'));

        return response()->json([
            'message' => 'Se o e-mail existir em nossa base, enviaremos as instruções de recuperação.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->resetPasswordUseCase->execute(
            email: $data['email'],
            token: $data['token'],
            password: $data['password'],
        );

        return response()->json([
            'message' => 'Senha redefinida com sucesso. Você já pode entrar com a nova senha.',
        ]);
    }
}
