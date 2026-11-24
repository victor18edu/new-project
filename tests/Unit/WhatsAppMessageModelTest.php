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

    private function contact(): Contact
    {
        return Contact::create([
            'name' => 'Cliente Teste',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => '11999999999',
        ]);
    }
}
