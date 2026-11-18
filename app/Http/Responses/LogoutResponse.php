<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * A invalidação da sessão e a regeneração do CSRF são feitas pelo
     * AuthenticatedSessionController do Fortify antes desta resposta.
     */
    public function toResponse($request)
    {
        return redirect('/login')->with('status', 'Você saiu com sucesso.');
    }
}
