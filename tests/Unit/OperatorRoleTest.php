<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\OperatorRole;
use Tests\TestCase;

class OperatorRoleTest extends TestCase
{
    public function test_labels_maps_each_role_to_its_portuguese_label(): void
    {
        $this->assertSame([
            OperatorRole::ADMIN => 'Administrador',
            OperatorRole::MANAGER => 'Gerente',
            OperatorRole::OPERATOR => 'Operador',
        ], OperatorRole::labels());
    }

    public function test_is_manager_accepts_only_active_admin_and_manager_roles(): void
    {
        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $this->assertTrue(OperatorRole::isManager($this->user($role)));
        }

        $this->assertFalse(OperatorRole::isManager($this->user(OperatorRole::OPERATOR)));
        $this->assertFalse(OperatorRole::isManager($this->user('unknown')));
    }

    public function test_is_manager_rejects_inactive_admin_and_manager(): void
    {
        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $this->assertFalse(OperatorRole::isManager($this->user($role, false)));
        }
    }

    public function test_can_view_all_conversations_follows_the_manager_check(): void
    {
        $this->assertTrue(OperatorRole::canViewAllConversations($this->user(OperatorRole::ADMIN)));
        $this->assertTrue(OperatorRole::canViewAllConversations($this->user(OperatorRole::MANAGER)));
        $this->assertFalse(OperatorRole::canViewAllConversations($this->user(OperatorRole::OPERATOR)));
        $this->assertFalse(OperatorRole::canViewAllConversations($this->user(OperatorRole::MANAGER, false)));
    }

    public function test_is_operator_accepts_every_active_known_role(): void
    {
        foreach (array_keys(OperatorRole::labels()) as $role) {
            $this->assertTrue(OperatorRole::isOperator($this->user($role)));
        }
    }

    public function test_is_operator_rejects_inactive_and_unknown_roles(): void
    {
        foreach (array_keys(OperatorRole::labels()) as $role) {
            $this->assertFalse(OperatorRole::isOperator($this->user($role, false)));
        }

        $this->assertFalse(OperatorRole::isOperator($this->user('unknown')));
    }

    private function user(string $role, bool $active = true): User
    {
        return new User(['role' => $role, 'is_active' => $active]);
    }
}
