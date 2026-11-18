<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeWebSessionToken
{
    /**
     * Revoga somente o token da sessão web atual, se ainda existir.
     */
    public function handle(Logout $event): void
    {
        $request = request();

        if (! $request->hasSession()) {
            return;
        }

        $tokenId = $request->session()->get('web_session_token_id');

        if ($tokenId !== null) {
            PersonalAccessToken::find($tokenId)?->delete();
        }

        $request->session()->forget(['web_session_token', 'web_session_token_id']);
    }
}
