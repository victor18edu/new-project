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

    public function updateMessage(string $id, string $remoteJid, string $number, string $body): void
    {
        $this->client->updateMessage($id, $remoteJid, $number, $body);
    }

    public function deleteForEveryone(string $id, string $remoteJid, bool $fromMe): void
    {
        $this->client->deleteForEveryone($id, $remoteJid, $fromMe);
    }
}
