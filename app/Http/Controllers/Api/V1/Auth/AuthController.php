<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends BaseApiController
{
    /**
     * Autentica o usuário e emite um token Sanctum com permissões explícitas.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        // Mensagem única para e-mail inexistente, senha inválida ou usuário inativo.
        if (! $user || ! $user->is_active || ! Hash::check($request->input('password'), $user->password)) {
            return $this->errorResponse(
                message: 'E-mail ou senha inválidos.',
                status: 401
            );
        }

        // Evita registrar um log de auditoria de "updated" a cada login.
        User::withoutEvents(fn () => $user->forceFill(['last_login_at' => now()])->save());

        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 480));

        $token = $user->createToken(
            'api-token',
            ['read', 'create', 'update', 'delete'],
            $expiresAt
        );

        return $this->successResponse(
            message: 'Login realizado com sucesso.',
            data: [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toIso8601String(),
            ]
        );
    }

    /**
     * Revoga somente o token usado na requisição atual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(
            message: 'Logout realizado com sucesso.'
        );
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse(
            message: 'Usuário autenticado.',
            data: [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        );
    }

    /**
     * Envia o link de redefinição com mensagem genérica, independentemente do e-mail existir.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return $this->successResponse(
            message: 'Se o e-mail estiver cadastrado, enviaremos o link de redefinição de senha.'
        );
    }
}
