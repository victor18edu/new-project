<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EvolutionApiClient
{
    private const TIMEOUT = 15;

    public function instanceState(): ?string
    {
        $response = $this->request()->get($this->path("instance/connectionState/{$this->instance()}"));

        if ($response->notFound()) {
            return null;
        }

        $state = $response->throw()->json('instance.state');

        return is_string($state) && $state !== '' ? $state : null;
    }

    public function createInstance(): void
    {
        $this->request()->post($this->path('instance/create'), [
            'instanceName' => $this->instance(),
            'integration' => 'WHATSAPP-BAILEYS',
            'qrcode' => true,
        ])->throw();
    }

    public function qrCode(): ?string
    {
        $response = $this->request()->get($this->path("instance/connect/{$this->instance()}"));

        if ($response->notFound()) {
            return null;
        }

        $qr = $response->throw()->json('base64');

        if (! is_string($qr) || ! str_starts_with($qr, 'data:image/png;base64,')) {
            return null;
        }

        return $qr;
    }

    public function configureWebhook(): void
    {
        $this->request()->post($this->path("webhook/set/{$this->instance()}"), [
            'webhook' => [
                'enabled' => true,
                'url' => config('services.evolution.webhook_url'),
                'headers' => [
                    'X-Evolution-Token' => config('services.evolution.webhook_token'),
                ],
                'byEvents' => false,
                'base64' => false,
                'events' => ['MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE', 'MESSAGES_EDITED'],
            ],
        ])->throw();
    }

    public function sendText(string $to, string $body): string
    {
        $response = $this->request()->post($this->path("message/sendText/{$this->instance()}"), [
            'number' => ltrim($to, '+'),
            'text' => $body,
        ])->throw();

        $providerMessageId = $response->json('key.id');

        if (! is_string($providerMessageId) || $providerMessageId === '') {
            throw new RuntimeException('A Evolution não retornou o identificador da mensagem enviada.');
        }

        return $providerMessageId;
    }

    public function updateMessage(string $id, string $remoteJid, string $number, string $body): void
    {
        $this->request()->post($this->path("chat/updateMessage/{$this->instance()}"), [
            'number' => ltrim($number, '+'),
            'key' => [
                'remoteJid' => $remoteJid,
                'fromMe' => true,
                'id' => $id,
            ],
            'text' => $body,
        ])->throw();
    }

    public function deleteForEveryone(string $id, string $remoteJid, bool $fromMe): void
    {
        $this->request()->delete($this->path("chat/deleteMessageForEveryone/{$this->instance()}"), [
            'id' => $id,
            'remoteJid' => $remoteJid,
            'fromMe' => $fromMe,
        ])->throw();
    }

    private function request(): PendingRequest
    {
        $url = config('services.evolution.url');
        $apiKey = config('services.evolution.api_key');

        if (blank($url) || blank($apiKey) || blank($this->instance())) {
            throw new RuntimeException('Evolution API não está configurada.');
        }

        return Http::baseUrl((string) $url)
            ->withHeaders(['apikey' => (string) $apiKey])
            ->connectTimeout(3)
            ->timeout(self::TIMEOUT);
    }

    private function path(string $path): string
    {
        return '/'.ltrim($path, '/');
    }

    private function instance(): ?string
    {
        $instance = config('services.evolution.instance');

        return is_string($instance) && $instance !== '' ? $instance : null;
    }
}
