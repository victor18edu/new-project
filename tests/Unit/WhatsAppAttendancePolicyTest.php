<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use App\Policies\WhatsAppAttendancePolicy;
use App\Support\OperatorRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppAttendancePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_managers_can_view_any_attendance(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $attendance = $this->attendance(User::factory()->create()->id);

        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $this->assertTrue($policy->view(User::factory()->create(['role' => $role]), $attendance));
        }
    }

    public function test_operator_can_view_their_own_attendance(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);

        $this->assertTrue($policy->view($operator, $this->attendance($operator->id)));
    }

    public function test_operator_can_view_an_attendance_of_a_contact_assigned_to_them(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $attendance = $this->attendance(User::factory()->create()->id, WhatsAppAttendance::STATUS_OPEN, $operator->id);

        $this->assertTrue($policy->view($operator, $attendance));
    }

    public function test_operator_cannot_view_an_unrelated_attendance(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $attendance = $this->attendance(
            User::factory()->create()->id,
            WhatsAppAttendance::STATUS_OPEN,
            User::factory()->create()->id
        );

        $this->assertFalse($policy->view($operator, $attendance));
    }

    public function test_inactive_manager_cannot_view_someone_elses_attendance(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $manager = User::factory()->create(['role' => OperatorRole::MANAGER, 'is_active' => false]);
        $attendance = $this->attendance(User::factory()->create()->id);

        $this->assertFalse($policy->view($manager, $attendance));
    }

    public function test_close_allows_an_active_manager_or_the_responsible_operator_for_open_attendances(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $attendance = $this->attendance($operator->id);
        $manager = User::factory()->create(['role' => OperatorRole::MANAGER]);

        $this->assertTrue($policy->close($operator, $attendance));
        $this->assertTrue($policy->close($manager, $attendance));
    }

    public function test_close_rejects_closed_and_transferred_attendances(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $manager = User::factory()->create(['role' => OperatorRole::MANAGER]);

        foreach ([WhatsAppAttendance::STATUS_CLOSED, WhatsAppAttendance::STATUS_TRANSFERRED] as $status) {
            $attendance = $this->attendance($operator->id, $status);

            $this->assertFalse($policy->close($operator, $attendance));
            $this->assertFalse($policy->close($manager, $attendance));
        }
    }

    public function test_close_rejects_unrelated_operators_and_inactive_managers(): void
    {
        $policy = new WhatsAppAttendancePolicy;
        $attendance = $this->attendance(User::factory()->create()->id);

        $this->assertFalse($policy->close(User::factory()->create(['role' => OperatorRole::OPERATOR]), $attendance));
        $this->assertFalse($policy->close(
            User::factory()->create(['role' => OperatorRole::MANAGER, 'is_active' => false]),
            $attendance
        ));
    }

    private function attendance(
        int $operatorId,
        string $status = WhatsAppAttendance::STATUS_OPEN,
        ?int $assignedUserId = null
    ): WhatsAppAttendance {
        $contact = Contact::factory()->create(['assigned_user_id' => $assignedUserId]);

        return WhatsAppAttendance::factory()->create([
            'contact_id' => $contact->id,
            'operator_id' => $operatorId,
            'status' => $status,
        ]);
    }
}
