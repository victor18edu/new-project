<?php

namespace Tests\Feature;

use App\Actions\ProcessWhatsAppWebhook;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.evolution.url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-key');
        config()->set('services.evolution.instance', 'crm');
    }

    public function test_history_requires_authentication(): void
    {
        $contact = $this->contactWithWhatsapp();

        $this->app['auth']->forgetGuards();

        $this->getJson("/api/v1/contacts/{$contact->id}/whatsapp-messages")
            ->assertUnauthorized();
    }

    public function test_history_returns_paginated_messages_for_the_contact(): void
    {
        $contact = $this->contactWithWhatsapp();
        $otherContact = $this->contactWithWhatsapp(['name' => 'Outro contato']);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $first = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Mensagem antiga',
        ]);
        $second = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'body' => 'Mensagem recente',
        ]);
        WhatsAppMessage::create([
            'contact_id' => $otherContact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Mensagem de outro contato',
        ]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}/whatsapp-messages")->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.pagination.total', 2);
        $response->assertJsonPath('data.items.0.id', $second->id);
        $response->assertJsonPath('data.items.1.id', $first->id);
        $response->assertJsonMissing(['body' => 'Mensagem de outro contato']);
    }

    public function test_valid_payload_sends_message_and_persists_history(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response([
                'key' => ['id' => 'BAE123'],
            ]),
        ]);

        $contact = $this->contactWithWhatsapp();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/contacts/{$contact->id}/whatsapp-messages", [
            'body' => 'Olá, tudo bem?',
        ])->assertCreated();

        $response->assertJsonPath('data.status', WhatsAppMessage::STATUS_SENT);
        $this->assertDatabaseHas('whatsapp_messages', [
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAE123',
            'body' => 'Olá, tudo bem?',
        ]);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://evolution.test/message/sendText/crm'
                && $request->hasHeader('apikey', 'test-key')
                && $request['number'] === '5511999999999'
                && $request['text'] === 'Olá, tudo bem?';
        });
    }

    public function test_send_job_merges_with_echo_that_arrived_first(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response([
                'key' => ['id' => 'BAERACE'],
            ]),
        ]);

        $contact = $this->contactWithWhatsapp();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        app(ProcessWhatsAppWebhook::class)->handle([
            'event' => 'messages.upsert',
            'instance' => 'crm',
            'data' => [
                'key' => [
                    'id' => 'BAERACE',
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => true,
                ],
                'message' => ['conversation' => 'Mensagem com corrida'],
                'messageTimestamp' => '1700000000',
            ],
        ]);

        $this->postJson("/api/v1/contacts/{$contact->id}/whatsapp-messages", [
            'body' => 'Mensagem com corrida',
        ])->assertCreated();

        $messages = WhatsAppMessage::where('provider_message_id', 'BAERACE')->get();

        $this->assertCount(1, $messages);
        $this->assertSame($user->id, $messages->first()->user_id);
        $this->assertSame(WhatsAppMessage::DIRECTION_OUTBOUND, $messages->first()->direction);
        $this->assertSame(WhatsAppMessage::STATUS_SENT, $messages->first()->status);
        $this->assertSame('Mensagem com corrida', $messages->first()->body);
        Http::assertSentCount(1);
    }

    public function test_rejects_message_when_contact_has_no_whatsapp(): void
    {
        Http::preventStrayRequests();
        $contact = $this->contactWithWhatsapp(['whatsapp' => null]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->postJson("/api/v1/contacts/{$contact->id}/whatsapp-messages", ['body' => 'Olá'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp');

        $this->assertDatabaseCount('whatsapp_messages', 0);
        Http::assertNothingSent();
    }

    public function test_rejects_empty_message_body(): void
    {
        Http::preventStrayRequests();
        $contact = $this->contactWithWhatsapp();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->postJson("/api/v1/contacts/{$contact->id}/whatsapp-messages", ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->assertDatabaseCount('whatsapp_messages', 0);
        Http::assertNothingSent();
    }

    public function test_failed_gateway_marks_message_as_failed(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response(['error' => 'invalid_token'], 400),
        ]);

        $contact = $this->contactWithWhatsapp();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $response = $this->postJson("/api/v1/contacts/{$contact->id}/whatsapp-messages", [
            'body' => 'Mensagem que falha',
        ])->assertCreated();

        $response->assertJsonPath('data.status', WhatsAppMessage::STATUS_FAILED);
        $this->assertDatabaseHas('whatsapp_messages', [
            'contact_id' => $contact->id,
            'status' => WhatsAppMessage::STATUS_FAILED,
            'body' => 'Mensagem que falha',
        ]);
    }

    public function test_web_page_requires_authentication(): void
    {
        $contact = $this->contactWithWhatsapp();

        $this->get("/contacts/{$contact->id}/whatsapp")->assertRedirect('/login');
    }

    public function test_web_page_renders_thread_and_escapes_message_body(): void
    {
        $this->actingAs(User::factory()->create());
        $contact = $this->contactWithWhatsapp();
        WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => '<script>alert("xss")</script>',
        ]);

        $this->get(route('web.contacts.whatsapp.show', $contact))
            ->assertOk()
            ->assertSee('Conversa no WhatsApp')
            ->assertSee('Enviar mensagem')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_web_page_shows_latest_message_at_the_bottom(): void
    {
        $this->actingAs(User::factory()->create());
        $contact = $this->contactWithWhatsapp();

        $older = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Mensagem antiga da thread',
        ]);
        $older->forceFill(['created_at' => now()->subMinutes(2)])->save();

        $newer = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'body' => 'Mensagem recente da thread',
        ]);
        $newer->forceFill(['created_at' => now()])->save();

        $this->get(route('web.contacts.whatsapp.show', $contact))
            ->assertOk()
            ->assertSeeInOrder(['Mensagem antiga da thread', 'Mensagem recente da thread']);
    }

    public function test_web_form_creates_message_and_redirects_to_thread(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response([
                'key' => ['id' => 'BAEWEB'],
            ]),
        ]);

        $this->actingAs(User::factory()->create());
        $contact = $this->contactWithWhatsapp();

        $this->post(route('web.contacts.whatsapp.store', $contact), ['body' => 'Mensagem pelo painel'])
            ->assertRedirect(route('web.contacts.whatsapp.show', $contact))
            ->assertSessionHas('status', 'Mensagem enfileirada para envio.');

        $this->assertDatabaseHas('whatsapp_messages', [
            'contact_id' => $contact->id,
            'body' => 'Mensagem pelo painel',
            'provider_message_id' => 'BAEWEB',
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);
    }

    private function contactWithWhatsapp(array $attributes = []): Contact
    {
        $company = Company::factory()->create();

        return Contact::create(array_merge([
            'name' => 'Cliente Teste',
            'company_id' => $company->id,
            'whatsapp' => '11999999999',
        ], $attributes));
    }
}
