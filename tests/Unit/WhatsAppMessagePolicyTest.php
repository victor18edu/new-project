<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Policies\WhatsAppMessagePolicy;
use App\Support\OperatorRole;
use Tests\TestCase;

class WhatsAppMessagePolicyTest extends TestCase
{
    public function test_managers_can_update_any_outbound_message_regardless_of_status(): void
    {
        $policy = new WhatsAppMessagePolicy;

        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $manager = $this->user($role, true, 99);

            foreach ($this->allStatuses() as $status) {
                $this->assertTrue($policy->update($manager, $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, $status)));
            }
        }
    }

    public function test_operator_can_update_only_their_own_queued_or_failed_messages(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $operator = $this->user(OperatorRole::OPERATOR, true, 10);

        foreach ($this->editableStatuses() as $status) {
            $this->assertTrue($policy->update($operator, $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, $status)));
        }
    }

    public function test_operator_cannot_update_sent_delivered_or_read_messages(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $operator = $this->user(OperatorRole::OPERATOR, true, 10);

        foreach ([
            WhatsAppMessage::STATUS_SENT,
            WhatsAppMessage::STATUS_DELIVERED,
            WhatsAppMessage::STATUS_READ,
        ] as $status) {
            $this->assertFalse($policy->update($operator, $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, $status)));
        }
    }

    public function test_inbound_messages_cannot_be_updated_even_by_managers(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $message = $this->message(10, WhatsAppMessage::DIRECTION_INBOUND, WhatsAppMessage::STATUS_RECEIVED);

        $this->assertFalse($policy->update($this->user(OperatorRole::MANAGER, true, 99), $message));
        $this->assertFalse($policy->update($this->user(OperatorRole::OPERATOR, true, 10), $message));
    }

    public function test_operator_cannot_update_another_users_message(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $operator = $this->user(OperatorRole::OPERATOR, true, 10);
        $message = $this->message(20, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_QUEUED);

        $this->assertFalse($policy->update($operator, $message));
    }

    public function test_inactive_managers_cannot_update_messages(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $manager = $this->user(OperatorRole::MANAGER, false, 99);

        $this->assertFalse($policy->update($manager, $this->message(10)));
    }

    public function test_managers_can_delete_any_message_including_inbound(): void
    {
        $policy = new WhatsAppMessagePolicy;

        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $manager = $this->user($role, true, 99);

            $this->assertTrue($policy->delete($manager, $this->message(10)));
            $this->assertTrue($policy->delete(
                $manager,
                $this->message(10, WhatsAppMessage::DIRECTION_INBOUND, WhatsAppMessage::STATUS_RECEIVED)
            ));
        }
    }

    public function test_operator_can_delete_only_their_own_outbound_messages(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $operator = $this->user(OperatorRole::OPERATOR, true, 10);

        $this->assertTrue($policy->delete($operator, $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_SENT)));
        $this->assertFalse($policy->delete($operator, $this->message(20)));
        $this->assertFalse($policy->delete(
            $operator,
            $this->message(10, WhatsAppMessage::DIRECTION_INBOUND, WhatsAppMessage::STATUS_RECEIVED)
        ));
    }

    public function test_inactive_users_cannot_delete_messages(): void
    {
        $policy = new WhatsAppMessagePolicy;

        $this->assertFalse($policy->delete($this->user(OperatorRole::MANAGER, false, 99), $this->message(10)));
        $this->assertFalse($policy->delete($this->user(OperatorRole::OPERATOR, false, 10), $this->message(10)));
    }

    public function test_managers_can_retry_any_failed_outbound_message(): void
    {
        $policy = new WhatsAppMessagePolicy;

        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $manager = $this->user($role, true, 99);
            $message = $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_FAILED);

            $this->assertTrue($policy->retry($manager, $message));
        }
    }

    public function test_operator_can_retry_only_their_own_failed_outbound_messages(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $operator = $this->user(OperatorRole::OPERATOR, true, 10);

        $this->assertTrue($policy->retry($operator, $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_FAILED)));
        $this->assertFalse($policy->retry($operator, $this->message(20, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_FAILED)));
        $this->assertFalse($policy->retry($operator, $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_QUEUED)));
        $this->assertFalse($policy->retry($operator, $this->message(10, WhatsAppMessage::DIRECTION_INBOUND, WhatsAppMessage::STATUS_RECEIVED)));
    }

    public function test_messages_with_a_provider_id_cannot_be_retried(): void
    {
        $policy = new WhatsAppMessagePolicy;
        $manager = $this->user(OperatorRole::ADMIN, true, 99);
        $message = $this->message(10, WhatsAppMessage::DIRECTION_OUTBOUND, WhatsAppMessage::STATUS_FAILED);
        $message->provider_message_id = 'BAE123';

        $this->assertFalse($policy->retry($manager, $message));
    }

    /** @return array<int, string> */
    private function allStatuses(): array
    {
        return [
            WhatsAppMessage::STATUS_QUEUED,
            WhatsAppMessage::STATUS_FAILED,
            WhatsAppMessage::STATUS_SENT,
            WhatsAppMessage::STATUS_DELIVERED,
            WhatsAppMessage::STATUS_READ,
        ];
    }

    /** @return array<int, string> */
    private function editableStatuses(): array
    {
        return [
            WhatsAppMessage::STATUS_QUEUED,
            WhatsAppMessage::STATUS_FAILED,
        ];
    }

    private function user(string $role, bool $active, int $id): User
    {
        $user = new User(['role' => $role, 'is_active' => $active]);
        $user->id = $id;

        return $user;
    }

    private function message(
        int $ownerId,
        string $direction = WhatsAppMessage::DIRECTION_OUTBOUND,
        string $status = WhatsAppMessage::STATUS_QUEUED
    ): WhatsAppMessage {
        return new WhatsAppMessage([
            'user_id' => $ownerId,
            'direction' => $direction,
            'status' => $status,
            'body' => 'Mensagem',
        ]);
    }
}
