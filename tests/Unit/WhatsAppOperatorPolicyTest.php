<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\User;
use App\Policies\CrmPolicy;
use App\Policies\WhatsAppOperatorPolicy;
use App\Support\OperatorRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppOperatorPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_inherits_the_generic_crm_abilities(): void
    {
        $policy = new WhatsAppOperatorPolicy;
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->assertInstanceOf(CrmPolicy::class, $policy);
        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $contact));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $contact));
        $this->assertTrue($policy->delete($user, $contact));
    }

    public function test_view_inbox_and_history_require_an_active_operator(): void
    {
        $policy = new WhatsAppOperatorPolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $inactive = User::factory()->create(['role' => OperatorRole::OPERATOR, 'is_active' => false]);
        $unknown = User::factory()->make(['role' => 'unknown']);

        $this->assertTrue($policy->viewInbox($operator));
        $this->assertTrue($policy->viewHistory($operator));
        $this->assertFalse($policy->viewInbox($inactive));
        $this->assertFalse($policy->viewHistory($inactive));
        $this->assertFalse($policy->viewInbox($unknown));
        $this->assertFalse($policy->viewHistory($unknown));
    }

    public function test_view_conversation_and_send_message_allow_any_active_operator(): void
    {
        $policy = new WhatsAppOperatorPolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $contact = Contact::factory()->create([
            'assigned_user_id' => User::factory()->create()->id,
        ]);

        $this->assertTrue($policy->viewConversation($operator, $contact));
        $this->assertTrue($policy->sendMessage($operator, $contact));
        $this->assertFalse($policy->viewConversation(
            User::factory()->create(['is_active' => false]),
            $contact
        ));
    }

    public function test_assign_allows_managers_for_any_contact(): void
    {
        $policy = new WhatsAppOperatorPolicy;
        $contact = Contact::factory()->create(['assigned_user_id' => null]);

        foreach ([OperatorRole::ADMIN, OperatorRole::MANAGER] as $role) {
            $this->assertTrue($policy->assign(User::factory()->create(['role' => $role]), $contact));
        }
    }

    public function test_assign_allows_an_operator_only_for_their_own_contact(): void
    {
        $policy = new WhatsAppOperatorPolicy;
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR]);
        $mine = Contact::factory()->create(['assigned_user_id' => $operator->id]);
        $unassigned = Contact::factory()->create(['assigned_user_id' => null]);
        $theirs = Contact::factory()->create(['assigned_user_id' => User::factory()->create()->id]);

        $this->assertTrue($policy->assign($operator, $mine));
        $this->assertFalse($policy->assign($operator, $unassigned));
        $this->assertFalse($policy->assign($operator, $theirs));
    }

    public function test_assign_denies_inactive_managers_and_operators(): void
    {
        $policy = new WhatsAppOperatorPolicy;
        $contact = Contact::factory()->create();

        $manager = User::factory()->create(['role' => OperatorRole::MANAGER, 'is_active' => false]);
        $operator = User::factory()->create(['role' => OperatorRole::OPERATOR, 'is_active' => false]);

        $this->assertFalse($policy->assign($manager, $contact));
        $this->assertFalse($policy->assign($operator, Contact::factory()->create(['assigned_user_id' => $operator->id])));
    }
}
