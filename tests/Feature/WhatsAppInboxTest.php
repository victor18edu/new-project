<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_requires_authentication(): void
    {
        $this->get(route('web.whatsapp.inbox'))->assertRedirect('/login');
    }

    public function test_sales_rep_sees_only_contacts_assigned_to_them(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->contactWithWhatsapp($user, ['name' => 'Contato Meu']);
        $theirs = $this->contactWithWhatsapp($other, ['name' => 'Contato Alheio']);
        $unassigned = $this->contactWithWhatsapp(null, ['name' => 'Contato Sem Responsavel']);

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox'))
            ->assertOk()
            ->assertSee('Contato Meu')
            ->assertDontSee('Contato Alheio')
            ->assertDontSee('Contato Sem Responsavel');

        $this->assertTrue($mine->assignedUser->is($user));
        $this->assertTrue($theirs->assignedUser->is($other));
        $this->assertNull($unassigned->assignedUser);
    }

    public function test_admin_and_manager_see_all_conversations(): void
    {
        $mine = $this->contactWithWhatsapp(User::factory()->create(), ['name' => 'Contato Meu']);
        $theirs = $this->contactWithWhatsapp(User::factory()->create(), ['name' => 'Contato Alheio']);
        $unassigned = $this->contactWithWhatsapp(null, ['name' => 'Contato Sem Responsavel']);

        foreach (['admin', 'manager'] as $role) {
            $manager = User::factory()->create(['role' => $role]);

            $this->actingAs($manager)
                ->get(route('web.whatsapp.inbox'))
                ->assertOk()
                ->assertSee('Contato Meu')
                ->assertSee('Contato Alheio')
                ->assertSee('Contato Sem Responsavel');
        }

        $this->assertNotNull($mine->id);
        $this->assertNotNull($theirs->id);
        $this->assertNotNull($unassigned->id);
    }

    public function test_inbox_orders_by_latest_message_and_paginates(): void
    {
        $user = User::factory()->create();

        $old = $this->contactWithWhatsapp($user, ['name' => 'Contato Antigo']);
        $this->messageFor($old, 'Mensagem antiga', now()->subDays(3));

        $recent = $this->contactWithWhatsapp($user, ['name' => 'Contato Recente']);
        $this->messageFor($recent, 'Mensagem recente', now());

        $never = $this->contactWithWhatsapp($user, ['name' => 'Contato Sem Mensagem']);

        foreach (range(1, 13) as $index) {
            $this->contactWithWhatsapp($user, ['name' => "Contato Extra {$index}"]);
        }

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox'))
            ->assertOk()
            ->assertViewHas('contacts', function (LengthAwarePaginator $contacts) use ($recent, $old): bool {
                $ids = $contacts->pluck('id')->all();

                return $contacts->total() === 16
                    && $contacts->perPage() === 15
                    && $ids[0] === $recent->id
                    && $ids[1] === $old->id;
            });

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox', ['page' => 2]))
            ->assertOk()
            ->assertSee('Contato Sem Mensagem');

        $this->assertNotNull($never->id);
    }

    public function test_inbox_search_filters_by_contact_name_and_company(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany(['trade_name' => 'Acme Inbox']);

        $this->contactWithWhatsapp($user, ['name' => 'Contato Buscado']);
        $this->contactWithWhatsapp($user, [
            'name' => 'Outro Contato',
            'company_id' => $company->id,
        ]);
        $this->contactWithWhatsapp($user, ['name' => 'Contato Oculto']);

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox', ['q' => 'Acme Inbox']))
            ->assertOk()
            ->assertSee('Outro Contato')
            ->assertDontSee('Contato Buscado')
            ->assertDontSee('Contato Oculto');

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox', ['q' => 'Buscado']))
            ->assertOk()
            ->assertSee('Contato Buscado')
            ->assertDontSee('Contato Oculto');
    }

    public function test_user_cannot_open_conversation_from_another_responsible(): void
    {
        $user = User::factory()->create();
        $other = $this->contactWithWhatsapp(User::factory()->create(), ['name' => 'Contato Alheio']);

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox.contact', $other))
            ->assertNotFound();
    }

    public function test_inbox_renders_conversation_and_sends_message(): void
    {
        config()->set('services.evolution.url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-key');
        config()->set('services.evolution.instance', 'crm');

        Http::preventStrayRequests();
        Http::fake([
            'evolution.test/message/sendText/crm' => Http::response([
                'key' => ['id' => 'BAEINBOX'],
            ]),
        ]);

        $user = User::factory()->create();
        $contact = $this->contactWithWhatsapp($user, ['name' => 'Contato Inbox']);
        $this->messageFor($contact, '<script>alert("xss")</script>', now());

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox.contact', $contact))
            ->assertOk()
            ->assertSee('Contato Inbox')
            ->assertSee('Enviar mensagem')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);

        $this->actingAs($user)
            ->post(route('web.whatsapp.inbox.store', $contact), ['body' => 'Mensagem pela inbox'])
            ->assertRedirect(route('web.whatsapp.inbox.contact', $contact))
            ->assertSessionHas('status', 'Mensagem enfileirada para envio.');

        $this->assertDatabaseHas('whatsapp_messages', [
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'body' => 'Mensagem pela inbox',
            'provider_message_id' => 'BAEINBOX',
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://evolution.test/message/sendText/crm'
                && $request->hasHeader('apikey', 'test-key')
                && $request['number'] === '5511999999999'
                && $request['text'] === 'Mensagem pela inbox';
        });
    }

    public function test_inbox_shows_latest_message_at_the_bottom(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactWithWhatsapp($user);
        $this->messageFor($contact, 'Mensagem antiga da inbox', now()->subMinutes(2));
        $this->messageFor($contact, 'Mensagem recente da inbox', now());

        $this->actingAs($user)
            ->get(route('web.whatsapp.inbox.contact', $contact))
            ->assertOk()
            ->assertSeeInOrder(['Mensagem antiga da inbox', 'Mensagem recente da inbox']);
    }

    public function test_contact_form_defaults_responsible_to_authenticated_user(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();

        $this->actingAs($user)
            ->post('/contacts', [
                'name' => 'Contato Novo',
                'company_id' => $company->id,
                'whatsapp' => '11999999999',
            ])
            ->assertRedirect(route('web.contacts.index'));

        $this->assertDatabaseHas('contacts', [
            'name' => 'Contato Novo',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_contact_form_can_set_responsible(): void
    {
        $user = User::factory()->create();
        $responsible = User::factory()->create();
        $company = $this->createCompany();

        $this->actingAs($user)
            ->post('/contacts', [
                'name' => 'Contato Atribuido',
                'company_id' => $company->id,
                'whatsapp' => '11999999999',
                'assigned_user_id' => $responsible->id,
            ])
            ->assertRedirect(route('web.contacts.index'));

        $this->assertDatabaseHas('contacts', [
            'name' => 'Contato Atribuido',
            'assigned_user_id' => $responsible->id,
        ]);
    }

    private function contactWithWhatsapp(?User $assigned, array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'name' => 'Contato '.fake()->unique()->numberBetween(1, 999999),
            'company_id' => $this->createCompany()->id,
            'whatsapp' => '11999999999',
            'assigned_user_id' => $assigned?->id,
        ], $attributes));
    }

    private function messageFor(Contact $contact, string $body, Carbon $timestamp): WhatsAppMessage
    {
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => $body,
            'sent_at' => $timestamp,
        ]);

        $message->forceFill(['created_at' => $timestamp])->save();

        return $message;
    }
}
