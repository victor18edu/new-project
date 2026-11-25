<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsAppMessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_sent_at_and_metadata(): void
    {
        $message = WhatsAppMessage::create([
            'contact_id' => $this->contact()->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Mensagem',
            'sent_at' => '2026-11-24 18:25:00',
            'metadata' => ['source' => 'phone_echo'],
        ]);

        $message->refresh();

        $this->assertInstanceOf(Carbon::class, $message->sent_at);
        $this->assertSame('2026-11-24 18:25:00', $message->sent_at->toDateTimeString());
        $this->assertSame(['source' => 'phone_echo'], $message->metadata);
    }

    public function test_contact_and_sender_relationships(): void
    {
        $contact = $this->contact();
        $user = User::factory()->create();

        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'body' => 'Mensagem',
        ]);

        $this->assertSame($contact->id, $message->contact->id);
        $this->assertSame($user->id, $message->sender->id);
    }

    public function test_status_rank_prevents_downgrades(): void
    {
        $rank = WhatsAppMessage::STATUS_RANK;

        $this->assertSame(0, $rank[WhatsAppMessage::STATUS_RECEIVED]);
        $this->assertSame(0, $rank[WhatsAppMessage::STATUS_QUEUED]);
        $this->assertSame(1, $rank[WhatsAppMessage::STATUS_SENT]);
        $this->assertSame(2, $rank[WhatsAppMessage::STATUS_DELIVERED]);
        $this->assertSame(3, $rank[WhatsAppMessage::STATUS_READ]);
        $this->assertGreaterThan($rank[WhatsAppMessage::STATUS_READ], $rank[WhatsAppMessage::STATUS_FAILED]);
    }

    public function test_casts_edited_at_and_deleted_at(): void
    {
        $message = WhatsAppMessage::create([
            'contact_id' => $this->contact()->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => 'Mensagem',
        ]);
        $editor = User::factory()->create();

        $message->forceFill([
            'edited_by' => $editor->id,
            'edited_at' => '2026-11-25 10:00:00',
            'deleted_by' => $editor->id,
        ])->save();
        $message->delete();
        $message->refresh();

        $this->assertInstanceOf(Carbon::class, $message->edited_at);
        $this->assertSame('2026-11-25 10:00:00', $message->edited_at->toDateTimeString());
        $this->assertInstanceOf(Carbon::class, $message->deleted_at);
    }

    public function test_editor_deleter_and_revision_relationships(): void
    {
        $editor = User::factory()->create();
        $deleter = User::factory()->create();
        $message = WhatsAppMessage::create([
            'contact_id' => $this->contact()->id,
            'user_id' => User::factory()->create()->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => 'Mensagem',
        ]);
        $revision = $message->revisions()->create([
            'editor_id' => $editor->id,
            'old_body' => 'Antes',
            'new_body' => 'Depois',
        ]);
        $message->forceFill([
            'edited_by' => $editor->id,
            'deleted_by' => $deleter->id,
        ])->save();

        $this->assertSame($editor->id, $message->editor->id);
        $this->assertSame($deleter->id, $message->deleter->id);
        $this->assertSame($revision->id, $message->revisions->first()->id);
        $this->assertSame($message->id, $revision->message->id);
        $this->assertSame($editor->id, $revision->editor->id);
    }

    public function test_soft_deletes_hide_the_message_from_default_queries(): void
    {
        $message = WhatsAppMessage::create([
            'contact_id' => $this->contact()->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Mensagem',
        ]);

        $message->delete();

        $this->assertNull(WhatsAppMessage::find($message->id));
        $this->assertNotNull(WhatsAppMessage::withTrashed()->find($message->id));
    }

    private function contact(): Contact
    {
        return Contact::create([
            'name' => 'Cliente Teste',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => '11999999999',
        ]);
    }
}
