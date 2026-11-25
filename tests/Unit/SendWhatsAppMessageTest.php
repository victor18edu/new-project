<?php

namespace Tests\Unit;

use App\Actions\SendWhatsAppMessage;
use App\Contracts\WhatsAppGateway;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SendWhatsAppMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_queued_outbound_message_and_dispatches_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $contact = Contact::create([
            'name' => 'Cliente Teste',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => '11999999999',
        ]);

        $message = (new SendWhatsAppMessage)->handle($contact, 'Olá', $user);

        $this->assertSame(WhatsAppMessage::DIRECTION_OUTBOUND, $message->direction);
        $this->assertSame(WhatsAppMessage::STATUS_QUEUED, $message->status);
        $this->assertSame('Olá', $message->body);
        $this->assertSame($user->id, $message->user_id);
        $this->assertSame($contact->id, $message->contact_id);

        Bus::assertDispatched(SendWhatsAppMessageJob::class, fn (SendWhatsAppMessageJob $job): bool => $job->messageId === $message->id);
    }

    public function test_failure_preserves_retry_metadata(): void
    {
        $contact = Contact::create([
            'name' => 'Cliente Teste',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => '11999999999',
        ]);
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => 'Olá',
            'metadata' => ['retry_attempts' => 2],
        ]);
        $gateway = Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldReceive('send')->once()->andThrow(new RuntimeException('Falha temporária.'));

        (new SendWhatsAppMessageJob($message->id))->handle($gateway);

        $message->refresh();
        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $message->status);
        $this->assertSame('send_failed', $message->metadata['error']);
        $this->assertSame(2, $message->metadata['retry_attempts']);
    }

    public function test_rejects_contact_without_a_valid_whatsapp_number(): void
    {
        Bus::fake();

        $contact = Contact::create([
            'name' => 'Sem WhatsApp',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('O contato não possui um número de WhatsApp válido.');

        try {
            (new SendWhatsAppMessage)->handle($contact, 'Olá');
        } finally {
            $this->assertDatabaseCount('whatsapp_messages', 0);
            Bus::assertNothingDispatched();
        }
    }
}
