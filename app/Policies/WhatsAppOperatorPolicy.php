<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use App\Support\OperatorRole;

/**
 * Extends CrmPolicy so the generic CRM screens keep their existing abilities
 * while the WhatsApp inbox adds scoped, role-aware ones.
 */
class WhatsAppOperatorPolicy extends CrmPolicy
{
    public function viewInbox(User $user): bool
    {
        return OperatorRole::isOperator($user);
    }

    /**
     * The inbox list is scoped by the controller. From a contact page any
     * active operator can still open and reply in the conversation.
     */
    public function viewConversation(User $user, Contact $contact): bool
    {
        return OperatorRole::isOperator($user);
    }

    public function sendMessage(User $user, Contact $contact): bool
    {
        return $this->viewConversation($user, $contact);
    }

    public function assign(User $user, Contact $contact): bool
    {
        if (OperatorRole::isManager($user)) {
            return true;
        }

        return OperatorRole::isOperator($user)
            && $contact->assigned_user_id === $user->id;
    }

    public function viewHistory(User $user): bool
    {
        return OperatorRole::isOperator($user);
    }
}
