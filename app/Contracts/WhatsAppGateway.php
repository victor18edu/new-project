<?php

namespace App\Contracts;

interface WhatsAppGateway
{
    /**
     * Send a text message and return the provider's message identifier.
     */
    public function send(string $to, string $body): string;

    /**
     * Edit a message that was already sent through the provider.
     */
    public function updateMessage(string $id, string $remoteJid, string $number, string $body): void;

    /**
     * Revoke a message for everyone in the chat.
     */
    public function deleteForEveryone(string $id, string $remoteJid, bool $fromMe): void;
}
