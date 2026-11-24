<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;

class EvolutionApiGateway implements WhatsAppGateway
{
    public function __construct(private readonly EvolutionApiClient $client) {}

    public function send(string $to, string $body): string
    {
        return $this->client->sendText($to, $body);
    }
}
