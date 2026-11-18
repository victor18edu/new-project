<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;

/**
 * Substitui a resposta padrão do Fortify para não revelar se o e-mail existe.
 */
class FailedPasswordResetLinkRequestResponse implements Responsable
{
    public function __construct(protected string $status)
    {
    }

    public function toResponse($request)
    {
        $message = __('passwords.sent');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 200);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', $message);
    }
}
