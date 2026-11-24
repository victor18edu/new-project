<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ProcessWhatsAppWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends BaseApiController
{
    public function handle(Request $request, ProcessWhatsAppWebhook $processor): Response
    {
        $configuredToken = config('services.evolution.webhook_token');
        $token = $request->header('X-Evolution-Token');

        if (blank($configuredToken)
            || ! is_string($token)
            || ! hash_equals((string) $configuredToken, $token)) {
            abort(403);
        }

        $payload = $request->json()->all();

        if (! is_array($payload)) {
            abort(400);
        }

        $processor->handle($payload);

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }
}
