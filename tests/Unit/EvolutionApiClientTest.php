<?php

namespace Tests\Unit;

use App\Services\WhatsApp\EvolutionApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EvolutionApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.evolution.url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-key');
        config()->set('services.evolution.instance', 'crm');
        config()->set('services.evolution.webhook_url', 'https://crm.test/api/v1/whatsapp/webhook');
        config()->set('services.evolution.webhook_token', 'webhook-token');

        Http::preventStrayRequests();
    }

    public function test_instance_state_returns_the_state_from_evolution(): void
    {
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response([
                'instance' => ['state' => 'open'],
            ]),
        ]);

        $this->assertSame('open', (new EvolutionApiClient)->instanceState());

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/instance/connectionState/crm'
            && $request->method() === 'GET'
            && $request->hasHeader('apikey', 'test-key'));
    }

    public function test_instance_state_returns_null_when_instance_does_not_exist(): void
    {
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response([], 404),
        ]);

        $this->assertNull((new EvolutionApiClient)->instanceState());
    }

    public function test_instance_state_returns_null_when_state_is_missing(): void
    {
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response(['instance' => []]),
        ]);

        $this->assertNull((new EvolutionApiClient)->instanceState());
    }

    public function test_create_instance_posts_expected_payload(): void
    {
        Http::fake([
            'evolution.test/instance/create' => Http::response([], 201),
        ]);

        (new EvolutionApiClient)->createInstance();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/instance/create'
            && $request->method() === 'POST'
            && $request['instanceName'] === 'crm'
            && $request['integration'] === 'WHATSAPP-BAILEYS'
            && $request['qrcode'] === true);
    }

    public function test_qr_code_returns_valid_data_uri(): void
    {
        Http::fake([
            'evolution.test/instance/connect/crm' => Http::response([
                'base64' => 'data:image/png;base64,QRCODE',
            ]),
        ]);

        $this->assertSame('data:image/png;base64,QRCODE', (new EvolutionApiClient)->qrCode());
    }

    public function test_qr_code_rejects_invalid_payload(): void
    {
        Http::fake([
            'evolution.test/instance/connect/crm' => Http::response(['base64' => 'not-a-qr']),
        ]);

        $this->assertNull((new EvolutionApiClient)->qrCode());
    }

    public function test_configure_webhook_posts_token_and_expected_events(): void
    {
        Http::fake([
            'evolution.test/webhook/set/crm' => Http::response([]),
        ]);

        (new EvolutionApiClient)->configureWebhook();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/webhook/set/crm'
            && $request->method() === 'POST'
            && data_get($request->data(), 'webhook.enabled') === true
            && data_get($request->data(), 'webhook.url') === 'https://crm.test/api/v1/whatsapp/webhook'
            && data_get($request->data(), 'webhook.headers.X-Evolution-Token') === 'webhook-token'
            && data_get($request->data(), 'webhook.events') === ['MESSAGES_UPSERT', 'MESSAGES_UPDATE']);
    }

    public function test_send_text_strips_the_plus_and_returns_provider_message_id(): void
    {
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response([
                'key' => ['id' => 'BAE123'],
            ]),
        ]);

        $id = (new EvolutionApiClient)->sendText('+5511999999999', 'Olá');

        $this->assertSame('BAE123', $id);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/message/sendText/crm'
            && $request->method() === 'POST'
            && $request['number'] === '5511999999999'
            && $request['text'] === 'Olá');
    }

    public function test_send_text_throws_when_provider_message_id_is_missing(): void
    {
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response(['key' => []]),
        ]);

        $this->expectException(RuntimeException::class);

        (new EvolutionApiClient)->sendText('5511999999999', 'Olá');
    }

    public function test_delete_for_everyone_sends_the_message_key_to_evolution(): void
    {
        Http::fake([
            'evolution.test/chat/deleteMessageForEveryone/crm' => Http::response([], 200),
        ]);

        (new EvolutionApiClient)->deleteForEveryone('BAE123', '5511999999999@s.whatsapp.net', true);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/chat/deleteMessageForEveryone/crm'
            && $request->method() === 'DELETE'
            && $request['id'] === 'BAE123'
            && $request['remoteJid'] === '5511999999999@s.whatsapp.net'
            && $request['fromMe'] === true
            && $request->hasHeader('apikey', 'test-key'));
    }

    public function test_requests_are_rejected_when_evolution_is_not_configured(): void
    {
        config()->set('services.evolution.api_key', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Evolution API não está configurada.');

        (new EvolutionApiClient)->instanceState();
    }
}
