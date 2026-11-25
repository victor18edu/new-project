<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsAppAttendanceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_started_at_and_ended_at(): void
    {
        $attendance = WhatsAppAttendance::factory()->create([
            'started_at' => '2026-11-24 18:00:00',
            'ended_at' => '2026-11-24 18:30:00',
        ])->refresh();

        $this->assertInstanceOf(Carbon::class, $attendance->started_at);
        $this->assertSame('2026-11-24 18:00:00', $attendance->started_at->toDateTimeString());
        $this->assertInstanceOf(Carbon::class, $attendance->ended_at);
        $this->assertSame('2026-11-24 18:30:00', $attendance->ended_at->toDateTimeString());
    }

    public function test_contact_operator_and_assigned_by_relationships(): void
    {
        $contact = Contact::factory()->create();
        $operator = User::factory()->create();
        $assignedBy = User::factory()->create();

        $attendance = WhatsAppAttendance::factory()->create([
            'contact_id' => $contact->id,
            'operator_id' => $operator->id,
            'assigned_by_id' => $assignedBy->id,
        ]);

        $this->assertSame($contact->id, $attendance->contact->id);
        $this->assertSame($operator->id, $attendance->operator->id);
        $this->assertSame($assignedBy->id, $attendance->assignedBy->id);
    }

    public function test_open_scope_only_matches_open_attendances(): void
    {
        $open = WhatsAppAttendance::factory()->count(2)->create(['status' => WhatsAppAttendance::STATUS_OPEN]);
        WhatsAppAttendance::factory()->create(['status' => WhatsAppAttendance::STATUS_CLOSED]);
        WhatsAppAttendance::factory()->create(['status' => WhatsAppAttendance::STATUS_TRANSFERRED]);

        $this->assertCount(2, WhatsAppAttendance::open()->get());
        $this->assertEqualsCanonicalizing(
            $open->pluck('id')->all(),
            WhatsAppAttendance::open()->pluck('id')->all()
        );
    }

    public function test_status_labels_use_portuguese_text(): void
    {
        $this->assertSame([
            WhatsAppAttendance::STATUS_OPEN => 'Em andamento',
            WhatsAppAttendance::STATUS_CLOSED => 'Encerrado',
            WhatsAppAttendance::STATUS_TRANSFERRED => 'Transferido',
        ], WhatsAppAttendance::STATUS_LABELS);
    }
}
