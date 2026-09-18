<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\UseCases\LoginUseCase;
use App\Modules\Auth\UseCases\LogoutUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LogoutUseCase $logoutUseCase,
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
}
