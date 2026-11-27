<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.evolution.url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-key');
        config()->set('services.evolution.instance', 'crm');
        config()->set('services.evolution.webhook_token', 'webhook-token');
        config()->set('services.evolution.webhook_url', 'http://host.test/api/v1/whatsapp/webhook');
    }

    public function test_connection_requires_authentication(): void
    {
        $this->get(route('web.whatsapp.connection'))->assertRedirect('/login');
    }

    public function test_manager_cannot_access_connection(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get(route('web.whatsapp.connection'))
            ->assertForbidden();
    }

    public function test_sales_rep_cannot_access_connection(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales_rep']))
            ->get(route('web.whatsapp.connection'))
            ->assertForbidden();
    }

    public function test_admin_sees_status_and_qr_code(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response(['instance' => ['state' => 'connecting']]),
            'evolution.test/instance/connect/crm' => Http::response(['base64' => 'data:image/png;base64,QUJD']),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('web.whatsapp.connection'))
            ->assertOk()
            ->assertViewHas('state', 'connecting')
            ->assertSee('data:image/png;base64,QUJD', false);
    }

    public function test_admin_sees_connected_state_without_fetching_qr_code(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response(['instance' => ['state' => 'open']]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('web.whatsapp.connection'))
            ->assertOk()
            ->assertViewHas('state', 'open')
            ->assertViewHas('qr', null);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/instance/connect/'));
    }

    public function test_admin_can_create_instance_and_configure_webhook(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response([], 404),
            'evolution.test/instance/create' => Http::response(['instance' => ['instanceName' => 'crm']], 201),
            'evolution.test/webhook/set/crm' => Http::response(['webhook' => ['enabled' => true]]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('web.whatsapp.connection.store'))
            ->assertRedirect(route('web.whatsapp.connection'))
            ->assertSessionHas('status', 'Instância e webhook configurados. Escaneie o QR Code se necessário.');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://evolution.test/instance/create'
                && $request->hasHeader('apikey', 'test-key')
                && $request['instanceName'] === 'crm'
                && $request['qrcode'] === true;
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://evolution.test/webhook/set/crm'
                && $request['webhook']['url'] === 'http://host.test/api/v1/whatsapp/webhook'
                && $request['webhook']['byEvents'] === false
                && $request['webhook']['base64'] === false
                && $request['webhook']['events'] === [
                    'MESSAGES_UPSERT',
                    'MESSAGES_UPDATE',
                    'MESSAGES_DELETE',
                    'MESSAGES_EDITED',
                ]
                && $request['webhook']['headers']['X-Evolution-Token'] === 'webhook-token';
        });
    }

    public function test_existing_instance_is_not_created_again(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response(['instance' => ['state' => 'open']]),
            'evolution.test/webhook/set/crm' => Http::response(['webhook' => ['enabled' => true]]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('web.whatsapp.connection.store'))
            ->assertRedirect(route('web.whatsapp.connection'));

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/instance/create'));
    }

    public function test_qr_endpoint_returns_json_for_admin(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/instance/connectionState/crm' => Http::response(['instance' => ['state' => 'connecting']]),
            'evolution.test/instance/connect/crm' => Http::response(['base64' => 'data:image/png;base64,WVg=']),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson(route('web.whatsapp.connection.qr'))
            ->assertOk()
            ->assertJsonPath('state', 'connecting')
            ->assertJsonPath('qr', 'data:image/png;base64,WVg=');
    }

    public function test_qr_endpoint_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales_rep']))
            ->getJson(route('web.whatsapp.connection.qr'))
            ->assertForbidden();
    }
}
