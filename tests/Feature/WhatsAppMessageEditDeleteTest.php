<?php

namespace Tests\Feature;

use App\Actions\ProcessWhatsAppWebhook;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppMessageEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_edit_a_message_and_the_original_text_is_preserved(): void
    {
        Http::preventStrayRequests();
        $manager = User::factory()->create(['role' => 'manager']);
        $sender = User::factory()->create();
        $message = $this->message($sender, [
            'status' => WhatsAppMessage::STATUS_SENT,
            'body' => 'Texto original',
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->putJson('/api/v1/contacts/'.$message->contact_id.'/whatsapp-messages/'.$message->id, [
            'body' => 'Texto corrigido',
        ])->assertOk()->assertJsonPath('data.body', 'Texto corrigido');

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'body' => 'Texto corrigido',
            'edited_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('whatsapp_message_revisions', [
            'message_id' => $message->id,
            'editor_id' => $manager->id,
            'old_body' => 'Texto original',
            'new_body' => 'Texto corrigido',
        ]);
        Http::assertNothingSent();
    }

    public function test_operator_can_edit_only_their_own_queued_or_failed_messages(): void
    {
        Http::preventStrayRequests();
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $contact = Contact::factory()->create(['assigned_user_id' => $operator->id]);

        $queued = $this->message($operator, [
            'contact' => $contact,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => 'Na fila',
        ]);
        $sent = $this->message($operator, [
            'contact' => $contact,
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);
        $other = $this->message(User::factory()->create(), [
            'contact' => $contact,
            'status' => WhatsAppMessage::STATUS_QUEUED,
        ]);
        Sanctum::actingAs($operator, ['*']);

        $this->putJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$queued->id, [
            'body' => 'Na fila (editada)',
        ])->assertOk();

        $this->putJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$sent->id, [
            'body' => 'Não permitido',
        ])->assertForbidden();

        $this->putJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$other->id, [
            'body' => 'Não permitido',
        ])->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_inbound_messages_cannot_be_edited(): void
    {
        $manager = User::factory()->create(['role' => 'admin']);
        $message = $this->message(null, [
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->putJson('/api/v1/contacts/'.$message->contact_id.'/whatsapp-messages/'.$message->id, [
            'body' => 'Editando mensagem recebida',
        ])->assertForbidden();
    }

    public function test_deleted_message_disappears_from_conversation_but_is_kept_for_audit(): void
    {
        Http::preventStrayRequests();
        $manager = User::factory()->create(['role' => 'admin']);
        $message = $this->message(User::factory()->create(), [
            'status' => WhatsAppMessage::STATUS_SENT,
            'body' => 'Mensagem que some da conversa',
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->deleteJson('/api/v1/contacts/'.$message->contact_id.'/whatsapp-messages/'.$message->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('whatsapp_messages', ['id' => $message->id, 'deleted_by' => $manager->id]);
        $this->getJson('/api/v1/contacts/'.$message->contact_id.'/whatsapp-messages')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);

        $this->actingAs($manager)
            ->get(route('web.whatsapp.history'))
            ->assertOk()
            ->assertSee('Mensagem que some da conversa');

        Http::assertNothingSent();
    }

    public function test_operator_can_delete_only_their_own_outbound_messages(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $contact = Contact::factory()->create(['assigned_user_id' => $operator->id]);
        $mine = $this->message($operator, ['contact' => $contact]);
        $theirs = $this->message(User::factory()->create(), ['contact' => $contact]);
        $inbound = $this->message(null, [
            'contact' => $contact,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
        ]);
        Sanctum::actingAs($operator, ['*']);

        $this->deleteJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$mine->id)->assertOk();
        $this->deleteJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$theirs->id)->assertForbidden();
        $this->deleteJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$inbound->id)->assertForbidden();
    }

    public function test_web_bubble_offers_edit_and_delete_only_when_allowed(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $message = $this->message(User::factory()->create(), [
            'body' => 'Mensagem com balão',
        ]);

        $this->actingAs($manager)
            ->get(route('web.contacts.whatsapp.show', $message->contact))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee('Apagar')
            ->assertSee('crm-confirm')
            ->assertSee('Apagar esta mensagem da conversa e do WhatsApp?')
            ->assertDontSee('Apagar para todos?')
            ->assertSee('Mensagem com balão');
    }

    public function test_web_actions_edit_and_delete_messages(): void
    {
        Http::preventStrayRequests();
        $manager = User::factory()->create(['role' => 'admin']);
        $message = $this->message(User::factory()->create(), [
            'body' => 'Mensagem via painel',
        ]);

        $this->actingAs($manager)
            ->put(route('web.whatsapp.messages.update', [$message->contact, $message]), ['body' => 'Mensagem editada via painel'])
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'body' => 'Mensagem editada via painel',
        ]);

        $this->actingAs($manager)
            ->delete(route('web.whatsapp.messages.destroy', [$message->contact, $message]))
            ->assertRedirect();

        $this->assertSoftDeleted('whatsapp_messages', ['id' => $message->id]);
        Http::assertNothingSent();
    }

    public function test_webhook_cannot_resurrect_or_update_a_deleted_message(): void
    {
        config()->set('services.evolution.url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-key');
        config()->set('services.evolution.instance', 'crm');
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/chat/deleteMessageForEveryone/crm' => Http::response([], 200),
        ]);
        $manager = User::factory()->create(['role' => 'manager']);
        $message = $this->message(null, [
            'status' => WhatsAppMessage::STATUS_SENT,
            'body' => 'Mensagem apagada',
            'provider_message_id' => 'BAEDELETED',
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->deleteJson('/api/v1/contacts/'.$message->contact_id.'/whatsapp-messages/'.$message->id)
            ->assertOk();

        app(ProcessWhatsAppWebhook::class)->handle([
            'event' => 'messages.update',
            'instance' => 'crm',
            'data' => [
                'key' => ['id' => 'BAEDELETED'],
                'update' => ['status' => 'READ'],
            ],
        ]);

        $this->assertSame(WhatsAppMessage::STATUS_SENT, $message->fresh()->status);
        $this->assertSoftDeleted('whatsapp_messages', ['id' => $message->id]);
        $this->assertSame(1, WhatsAppMessage::withTrashed()->where('provider_message_id', 'BAEDELETED')->count());
    }

    public function test_send_job_keeps_a_deleted_echo_and_does_not_reuse_its_provider_id(): void
    {
        config()->set('services.evolution.url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-key');
        config()->set('services.evolution.instance', 'crm');
        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response([
                'key' => ['id' => 'BAEECHO'],
            ]),
            'evolution.test/chat/deleteMessageForEveryone/crm' => Http::response([], 200),
        ]);

        $contact = Contact::factory()->create(['whatsapp' => '11999999999']);
        app(ProcessWhatsAppWebhook::class)->handle([
            'event' => 'messages.upsert',
            'instance' => 'crm',
            'data' => [
                'key' => [
                    'id' => 'BAEECHO',
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => true,
                ],
                'message' => ['conversation' => 'Echo apagado'],
                'messageTimestamp' => '1700000000',
            ],
        ]);

        $echo = WhatsAppMessage::where('provider_message_id', 'BAEECHO')->firstOrFail();
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager, ['*']);

        $this->deleteJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$echo->id)
            ->assertOk();

        $this->postJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages', [
            'body' => 'Mensagem canonica',
        ])->assertCreated();

        $canonical = WhatsAppMessage::where('body', 'Mensagem canonica')->firstOrFail();

        $this->assertSoftDeleted('whatsapp_messages', ['id' => $echo->id]);
        $this->assertNull($canonical->provider_message_id);
        $this->assertSame(WhatsAppMessage::STATUS_SENT, $canonical->status);
        $this->assertTrue($canonical->metadata['echo_deleted']);
        $this->assertDatabaseCount('whatsapp_messages', 2);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'http://evolution.test/chat/deleteMessageForEveryone/crm');
    }

    public function test_failed_message_can_be_retried_without_creating_a_duplicate(): void
    {
        Queue::fake();
        $manager = User::factory()->create(['role' => 'admin']);
        $message = $this->message($manager, [
            'status' => WhatsAppMessage::STATUS_FAILED,
            'body' => 'Mensagem falhada',
            'metadata' => ['error' => 'send_failed'],
        ]);

        $this->actingAs($manager)
            ->post(route('web.whatsapp.messages.retry', [$message->contact, $message]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Mensagem reenviada para a fila de envio.');

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'status' => WhatsAppMessage::STATUS_QUEUED,
        ]);
        $this->assertSame(1, WhatsAppMessage::count());
        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
    }

    public function test_api_retry_is_forbidden_for_another_users_message(): void
    {
        Queue::fake();
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $contact = Contact::factory()->create(['assigned_user_id' => $operator->id]);
        $message = $this->message(User::factory()->create(), [
            'contact' => $contact,
            'status' => WhatsAppMessage::STATUS_FAILED,
        ]);
        Sanctum::actingAs($operator, ['*']);

        $this->postJson('/api/v1/contacts/'.$contact->id.'/whatsapp-messages/'.$message->id.'/retry')
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    /** @param array<string, mixed> $attributes */
    private function message(?User $sender, array $attributes = []): WhatsAppMessage
    {
        $contact = $attributes['contact'] ?? Contact::factory()->create([
            'assigned_user_id' => $sender?->id,
        ]);
        unset($attributes['contact']);

        return WhatsAppMessage::create(array_merge([
            'contact_id' => $contact->id,
            'user_id' => $sender?->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => 'Mensagem de teste',
        ], $attributes));
    }
}
