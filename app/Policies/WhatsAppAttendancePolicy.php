<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppAttendance;
use App\Support\OperatorRole;

class WhatsAppAttendancePolicy
{
    public function view(User $user, WhatsAppAttendance $attendance): bool
    {
        if (OperatorRole::canViewAllConversations($user)) {
            return true;
        }

        return $attendance->operator_id === $user->id
            || $attendance->contact?->assigned_user_id === $user->id;
    }

    public function close(User $user, WhatsAppAttendance $attendance): bool
    {
        return $attendance->status === WhatsAppAttendance::STATUS_OPEN
            && (OperatorRole::isManager($user) || $attendance->operator_id === $user->id);
    }
}
