<?php

namespace Tests\Unit;

use App\Actions\CloseWhatsAppAttendance;
use App\Actions\StartWhatsAppAttendance;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use App\Support\OperatorRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsAppAttendanceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_creates_an_open_attendance_and_assigns_the_contact(): void
    {
        $manager = User::factory()->create(['role' => OperatorRole::MANAGER]);
        $contact = Contact::factory()->create(['assigned_user_id' => null]);

        $attendance = (new StartWhatsAppAttendance)->handle($contact, $manager, $manager);

        $this->assertSame(WhatsAppAttendance::STATUS_OPEN, $attendance->status);
        $this->assertSame($manager->id, $attendance->operator_id);
        $this->assertSame($manager->id, $attendance->assigned_by_id);
        $this->assertInstanceOf(Carbon::class, $attendance->started_at);
        $this->assertSame($manager->id, $contact->fresh()->assigned_user_id);
        $this->assertDatabaseHas('whatsapp_attendances', [
            'id' => $attendance->id,
            'contact_id' => $contact->id,
        ]);
    }

    public function test_start_without_assigned_by_keeps_the_field_null(): void
    {
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $contact = Contact::factory()->create(['assigned_user_id' => null]);

        $attendance = (new StartWhatsAppAttendance)->handle($contact, $operator);

        $this->assertNull($attendance->assigned_by_id);
        $this->assertSame($operator->id, $contact->fresh()->assigned_user_id);
    }

    public function test_start_transfers_previous_open_attendances(): void
    {
        $manager = User::factory()->create(['role' => OperatorRole::MANAGER]);
        $previousOperator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $contact = Contact::factory()->create(['assigned_user_id' => $previousOperator->id]);
        $previous = WhatsAppAttendance::factory()->create([
            'contact_id' => $contact->id,
            'operator_id' => $previousOperator->id,
        ]);

        $current = (new StartWhatsAppAttendance)->handle($contact, $manager, $manager);

        $this->assertSame(WhatsAppAttendance::STATUS_TRANSFERRED, $previous->fresh()->status);
        $this->assertInstanceOf(Carbon::class, $previous->fresh()->ended_at);
        $this->assertSame(1, $contact->whatsappAttendances()->open()->count());
        $this->assertSame($current->id, $contact->whatsappAttendances()->open()->first()->id);
    }

    public function test_close_sets_status_end_time_and_outcome_notes(): void
    {
        $attendance = WhatsAppAttendance::factory()->create();

        $closed = (new CloseWhatsAppAttendance)->handle($attendance, 'Proposta enviada');

        $this->assertSame(WhatsAppAttendance::STATUS_CLOSED, $closed->status);
        $this->assertInstanceOf(Carbon::class, $closed->ended_at);
        $this->assertSame('Proposta enviada', $closed->outcome_notes);
        $this->assertDatabaseHas('whatsapp_attendances', [
            'id' => $attendance->id,
            'status' => WhatsAppAttendance::STATUS_CLOSED,
            'outcome_notes' => 'Proposta enviada',
        ]);
    }

    public function test_close_keeps_outcome_notes_null_when_not_provided(): void
    {
        $attendance = WhatsAppAttendance::factory()->create();

        $closed = (new CloseWhatsAppAttendance)->handle($attendance);

        $this->assertSame(WhatsAppAttendance::STATUS_CLOSED, $closed->status);
        $this->assertNull($closed->outcome_notes);
        $this->assertInstanceOf(Carbon::class, $closed->ended_at);
    }
}
