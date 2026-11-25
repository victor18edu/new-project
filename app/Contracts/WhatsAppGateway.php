<?php

namespace App\Contracts;

interface WhatsAppGateway
{
    /**
     * Send a text message and return the provider's message identifier.
     */
    public function send(string $to, string $body): string;

    /**
     * Revoke a message for everyone in the chat.
     */
    public function deleteForEveryone(string $id, string $remoteJid, bool $fromMe): void;
}
