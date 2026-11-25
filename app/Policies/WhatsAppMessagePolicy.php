<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\OperatorRole;

class WhatsAppMessagePolicy
{
    public function update(User $user, WhatsAppMessage $message): bool
    {
        if (! OperatorRole::isOperator($user) || $message->direction !== WhatsAppMessage::DIRECTION_OUTBOUND) {
            return false;
        }

        if (! OperatorRole::isManager($user) && $message->user_id !== $user->id) {
            return false;
        }

        return OperatorRole::isManager($user)
            || in_array($message->status, [WhatsAppMessage::STATUS_QUEUED, WhatsAppMessage::STATUS_FAILED], true);
    }

    public function retry(User $user, WhatsAppMessage $message): bool
    {
        return $this->update($user, $message)
            && $message->status === WhatsAppMessage::STATUS_FAILED
            && $message->provider_message_id === null
            && ! $message->trashed();
    }

    public function delete(User $user, WhatsAppMessage $message): bool
    {
        if (! OperatorRole::isOperator($user)) {
            return false;
        }

        if (OperatorRole::isManager($user)) {
            return true;
        }

        return $message->direction === WhatsAppMessage::DIRECTION_OUTBOUND
            && $message->user_id === $user->id;
    }
}
