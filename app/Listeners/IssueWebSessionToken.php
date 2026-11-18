<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;

class IssueWebSessionToken
{
    /**
     * Emite o token da sessão web e o guarda exclusivamente na sessão Laravel.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 480));

        $token = $user->createToken(
            'web-session',
            ['read', 'create', 'update', 'delete'],
            $expiresAt
        );

        Session::put('web_session_token', $token->plainTextToken);
        Session::put('web_session_token_id', $token->accessToken->id);
    }
}
