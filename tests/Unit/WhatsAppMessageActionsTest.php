<?php

namespace Tests\Unit;

use App\Actions\DeleteWhatsAppMessage;
use App\Actions\EditWhatsAppMessage;
use App\Actions\RetryWhatsAppMessage;
use App\Contracts\WhatsAppGateway;
use App\Exceptions\WhatsAppRemoteEditFailed;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WhatsAppMessageActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_creates_a_revision_and_updates_a_queued_message_locally(): void
    {
        $message = $this->message('Texto original');
        $editor = User::factory()->create();

        $edited = (new EditWhatsAppMessage($this->gateway()))->handle($message, 'Texto novo', $editor);

        $this->assertSame('Texto novo', $edited->body);
        $this->assertSame($editor->id, $edited->edited_by);
        $this->assertInstanceOf(Carbon::class, $edited->edited_at);
        $this->assertSame('skipped', $edited->metadata['remote_edit']);
        $this->assertDatabaseHas('whatsapp_message_revisions', [
            'message_id' => $message->id,
            'editor_id' => $editor->id,
            'old_body' => 'Texto original',
            'new_body' => 'Texto novo',
        ]);
    }

    public function test_consecutive_edits_keep_the_original_text_in_the_revision_chain(): void
    {
        $message = $this->message('Texto original');
        $editor = User::factory()->create();
        $action = new EditWhatsAppMessage($this->gateway());

        $action->handle($message, 'Primeira edição', $editor);
        $action->handle($message, 'Segunda edição', $editor);

        $revisions = $message->revisions()->orderBy('id')->get();

        $this->assertCount(2, $revisions);
        $this->assertSame('Texto original', $revisions[0]->old_body);
        $this->assertSame('Primeira edição', $revisions[0]->new_body);
        $this->assertSame('Primeira edição', $revisions[1]->old_body);
        $this->assertSame('Segunda edição', $revisions[1]->new_body);
        $this->assertSame('Segunda edição', $message->fresh()->body);
    }

    public function test_edit_updates_a_sent_message_on_whatsapp_before_saving_the_revision(): void
    {
        $message = $this->message('Texto original', [
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAE123',
            'metadata' => ['to' => '5511999999999@s.whatsapp.net'],
        ]);
        $editor = User::factory()->create();
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldReceive('updateMessage')
            ->once()
            ->with('BAE123', '5511999999999@s.whatsapp.net', '5511999999999', 'Texto novo');

        $edited = (new EditWhatsAppMessage($gateway))->handle($message, 'Texto novo', $editor);

        $this->assertSame('Texto novo', $edited->body);
        $this->assertSame($editor->id, $edited->edited_by);
        $this->assertInstanceOf(Carbon::class, $edited->edited_at);
        $this->assertSame('edited', $edited->metadata['remote_edit']);
        $this->assertDatabaseHas('whatsapp_message_revisions', [
            'message_id' => $message->id,
            'editor_id' => $editor->id,
            'old_body' => 'Texto original',
            'new_body' => 'Texto novo',
        ]);
    }

    public function test_edit_does_not_save_anything_when_whatsapp_rejects_the_change(): void
    {
        $message = $this->message('Texto original', [
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAE123',
            'metadata' => ['to' => '5511999999999@s.whatsapp.net'],
        ]);
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldReceive('updateMessage')
            ->once()
            ->with('BAE123', '5511999999999@s.whatsapp.net', '5511999999999', 'Texto novo')
            ->andThrow(new RuntimeException('Evolution recusou a edição.'));

        try {
            (new EditWhatsAppMessage($gateway))->handle($message, 'Texto novo', User::factory()->create());
            $this->fail('A edição não deveria ser salva quando a Evolution falha.');
        } catch (WhatsAppRemoteEditFailed $exception) {
            $this->assertSame(
                WhatsAppRemoteEditFailed::MESSAGE,
                $exception->getMessage(),
            );
        }

        $fresh = $message->fresh();
        $this->assertSame('Texto original', $fresh->body);
        $this->assertNull($fresh->edited_by);
        $this->assertNull($fresh->edited_at);
        $this->assertArrayNotHasKey('remote_edit', $fresh->metadata);
        $this->assertDatabaseMissing('whatsapp_message_revisions', ['message_id' => $message->id]);
    }

    public function test_edit_fails_when_a_sent_message_has_no_whatsapp_target(): void
    {
        $message = $this->message('Texto original', [
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAE123',
            'contact' => Contact::factory()->create(['whatsapp' => null]),
        ]);
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldNotReceive('updateMessage');

        try {
            (new EditWhatsAppMessage($gateway))->handle($message, 'Texto novo', User::factory()->create());
            $this->fail('A edição não deveria ser salva sem um destinatário no WhatsApp.');
        } catch (WhatsAppRemoteEditFailed $exception) {
            $this->assertSame(
                WhatsAppRemoteEditFailed::MESSAGE,
                $exception->getMessage(),
            );
        }

        $this->assertSame('Texto original', $message->fresh()->body);
        $this->assertDatabaseMissing('whatsapp_message_revisions', ['message_id' => $message->id]);
    }

    public function test_delete_soft_deletes_the_message_and_records_the_deleter(): void
    {
        $message = $this->message('Texto original');
        (new EditWhatsAppMessage($this->gateway()))->handle($message, 'Texto editado', User::factory()->create());
        $deleter = User::factory()->create();

        (new DeleteWhatsAppMessage($this->gateway()))->handle($message, $deleter);

        $this->assertSoftDeleted($message);
        $this->assertSame($deleter->id, $message->refresh()->deleted_by);
        $this->assertInstanceOf(Carbon::class, $message->refresh()->deleted_at);
        $this->assertSame(1, WhatsAppMessageRevision::where('message_id', $message->id)->count());
        $this->assertSame('Texto original', WhatsAppMessageRevision::where('message_id', $message->id)->first()->old_body);
    }

    public function test_delete_hides_the_message_from_default_queries_but_keeps_it_with_trashed(): void
    {
        $message = $this->message();

        (new DeleteWhatsAppMessage($this->gateway()))->handle($message, User::factory()->create());

        $this->assertFalse(WhatsAppMessage::query()->whereKey($message->id)->exists());
        $this->assertTrue(WhatsAppMessage::withTrashed()->whereKey($message->id)->exists());
    }

    public function test_delete_revokes_the_message_on_whatsapp_and_records_it(): void
    {
        $message = $this->message('Texto original', [
            'provider_message_id' => 'BAE123',
            'metadata' => ['to' => '5511999999999@s.whatsapp.net'],
        ]);
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldReceive('deleteForEveryone')
            ->once()
            ->with('BAE123', '5511999999999@s.whatsapp.net', true);

        $status = (new DeleteWhatsAppMessage($gateway))->handle($message, User::factory()->create());

        $this->assertSame('deleted', $status);
        $this->assertSoftDeleted($message);
        $this->assertSame('deleted', $message->refresh()->metadata['remote_delete']);
    }

    public function test_delete_still_hides_the_message_when_whatsapp_revocation_fails(): void
    {
        $message = $this->message('Texto original', [
            'provider_message_id' => 'BAE123',
            'metadata' => ['to' => '5511999999999'],
        ]);
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldReceive('deleteForEveryone')
            ->once()
            ->with('BAE123', '5511999999999@s.whatsapp.net', true)
            ->andThrow(new RuntimeException('Evolution indisponível.'));

        $status = (new DeleteWhatsAppMessage($gateway))->handle($message, User::factory()->create());

        $this->assertSame('failed', $status);
        $this->assertSoftDeleted($message);
        $this->assertSame('failed', $message->refresh()->metadata['remote_delete']);
    }

    public function test_retry_requeues_a_failed_message_without_creating_a_duplicate(): void
    {
        Queue::fake();
        $message = $this->message('Texto falhado', [
            'status' => WhatsAppMessage::STATUS_FAILED,
            'metadata' => ['error' => 'send_failed'],
        ]);

        $retried = (new RetryWhatsAppMessage)->handle($message);

        $this->assertSame(WhatsAppMessage::STATUS_QUEUED, $retried->status);
        $this->assertArrayNotHasKey('error', $retried->metadata);
        $this->assertSame(1, $retried->metadata['retry_attempts']);
        $this->assertSame(1, WhatsAppMessage::count());
        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
    }

    public function test_retry_rejects_messages_that_are_not_failed_outbound(): void
    {
        Queue::fake();
        $message = $this->message('Texto enviado', ['status' => WhatsAppMessage::STATUS_SENT]);

        try {
            (new RetryWhatsAppMessage)->handle($message);
            $this->fail('Mensagem enviada não deveria ser reenviada.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        Queue::assertNothingPushed();
    }

    private function gateway(): WhatsAppGateway
    {
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldNotReceive('updateMessage');
        $gateway->shouldNotReceive('deleteForEveryone');

        return $gateway;
    }

    /** @param array<string, mixed> $attributes */
    private function message(string $body = 'Texto original', array $attributes = []): WhatsAppMessage
    {
        $contact = $attributes['contact'] ?? Contact::factory()->create();
        unset($attributes['contact']);

        return WhatsAppMessage::create(array_merge([
            'contact_id' => $contact->id,
            'user_id' => User::factory()->create()->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => $body,
        ], $attributes));
    }
}
