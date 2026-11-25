<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_start_an_attendance_and_contact_becomes_assigned(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $contact = Contact::factory()->create(['assigned_user_id' => null]);
        Sanctum::actingAs($manager, ['*']);

        $response = $this->postJson('/api/v1/contacts/'.$contact->id.'/whatsapp-attendances')
            ->assertCreated()
            ->assertJsonPath('data.status', WhatsAppAttendance::STATUS_OPEN);

        $this->assertDatabaseHas('whatsapp_attendances', [
            'id' => $response->json('data.id'),
            'contact_id' => $contact->id,
            'operator_id' => $manager->id,
            'assigned_by_id' => $manager->id,
        ]);
        $this->assertSame($manager->id, $contact->fresh()->assigned_user_id);
    }

    public function test_starting_a_new_attendance_transfers_the_previous_open_one(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $contact = Contact::factory()->create(['assigned_user_id' => $operator->id]);

        $previous = WhatsAppAttendance::factory()->create([
            'contact_id' => $contact->id,
            'operator_id' => $operator->id,
        ]);

        Sanctum::actingAs($manager, ['*']);
        $this->postJson('/api/v1/contacts/'.$contact->id.'/whatsapp-attendances')->assertCreated();

        $this->assertSame(WhatsAppAttendance::STATUS_TRANSFERRED, $previous->fresh()->status);
        $this->assertNotNull($previous->fresh()->ended_at);
        $this->assertSame(1, $contact->whatsappAttendances()->open()->count());
    }

    public function test_operator_cannot_take_a_contact_assigned_to_another_operator(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $contact = Contact::factory()->create(['assigned_user_id' => User::factory()->create()->id]);
        Sanctum::actingAs($operator, ['*']);

        $this->postJson('/api/v1/contacts/'.$contact->id.'/whatsapp-attendances')
            ->assertForbidden();
    }

    public function test_api_lists_only_the_operators_own_attendances(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $other = User::factory()->create(['role' => 'sales_rep']);
        $mine = WhatsAppAttendance::factory()->create(['operator_id' => $operator->id]);
        WhatsAppAttendance::factory()->create(['operator_id' => $other->id]);
        Sanctum::actingAs($operator, ['*']);

        $this->getJson('/api/v1/whatsapp-attendances')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $mine->id);
    }

    public function test_manager_sees_all_attendances_and_can_close_one(): void
    {
        $manager = User::factory()->create(['role' => 'admin']);
        $attendance = WhatsAppAttendance::factory()->create([
            'operator_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->getJson('/api/v1/whatsapp-attendances')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1);

        $this->postJson('/api/v1/whatsapp-attendances/'.$attendance->id.'/close', [
            'outcome_notes' => 'Proposta enviada',
        ])->assertOk()->assertJsonPath('data.status', WhatsAppAttendance::STATUS_CLOSED);

        $this->assertDatabaseHas('whatsapp_attendances', [
            'id' => $attendance->id,
            'status' => WhatsAppAttendance::STATUS_CLOSED,
            'outcome_notes' => 'Proposta enviada',
        ]);
    }

    public function test_operator_can_close_their_own_attendance_but_not_another_one(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $mine = WhatsAppAttendance::factory()->create(['operator_id' => $operator->id]);
        $theirs = WhatsAppAttendance::factory()->create([
            'operator_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs($operator, ['*']);

        $this->postJson('/api/v1/whatsapp-attendances/'.$mine->id.'/close')->assertOk();
        $this->postJson('/api/v1/whatsapp-attendances/'.$theirs->id.'/close')->assertForbidden();
    }

    public function test_history_page_is_a_separate_page_with_close_action(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $attendance = WhatsAppAttendance::factory()->create(['operator_id' => $operator->id]);

        $this->actingAs($operator)
            ->get(route('web.whatsapp.history'))
            ->assertOk()
            ->assertSee('Histórico de atendimentos')
            ->assertSee($attendance->contact->name);

        $this->actingAs($operator)
            ->post(route('web.whatsapp.history.close', $attendance), ['outcome_notes' => 'Resolvido'])
            ->assertRedirect();

        $this->assertSame(WhatsAppAttendance::STATUS_CLOSED, $attendance->fresh()->status);
    }

    public function test_history_requires_an_active_operator(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);

        $this->actingAs($inactive)
            ->get(route('web.whatsapp.history'))
            ->assertForbidden();
    }

    public function test_inactive_manager_cannot_assume_or_close_attendances(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => false]);
        $contact = Contact::factory()->create(['assigned_user_id' => null]);
        $attendance = WhatsAppAttendance::factory()->create([
            'operator_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->postJson('/api/v1/contacts/'.$contact->id.'/whatsapp-attendances')
            ->assertForbidden();
        $this->postJson('/api/v1/whatsapp-attendances/'.$attendance->id.'/close')
            ->assertForbidden();

        $this->assertDatabaseHas('whatsapp_attendances', [
            'id' => $attendance->id,
            'status' => WhatsAppAttendance::STATUS_OPEN,
        ]);
    }

    public function test_api_show_returns_an_attendance_owned_by_the_operator(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $attendance = WhatsAppAttendance::factory()->create(['operator_id' => $operator->id]);
        Sanctum::actingAs($operator, ['*']);

        $this->getJson('/api/v1/whatsapp-attendances/'.$attendance->id)
            ->assertOk()
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.status', WhatsAppAttendance::STATUS_OPEN)
            ->assertJsonPath('data.contact.id', $attendance->contact_id);
    }

    public function test_api_show_allows_a_manager_to_view_another_operators_attendance(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $attendance = WhatsAppAttendance::factory()->create([
            'operator_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->getJson('/api/v1/whatsapp-attendances/'.$attendance->id)
            ->assertOk()
            ->assertJsonPath('data.id', $attendance->id);
    }

    public function test_api_show_denies_an_unrelated_operator(): void
    {
        $operator = User::factory()->create(['role' => 'sales_rep']);
        $attendance = WhatsAppAttendance::factory()->create([
            'operator_id' => User::factory()->create()->id,
            'contact_id' => Contact::factory()->create(['assigned_user_id' => null])->id,
        ]);
        Sanctum::actingAs($operator, ['*']);

        $this->getJson('/api/v1/whatsapp-attendances/'.$attendance->id)
            ->assertForbidden();
    }
}
