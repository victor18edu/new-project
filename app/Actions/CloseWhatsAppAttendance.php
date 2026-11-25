<?php

namespace App\Actions;

use App\Models\WhatsAppAttendance;

final class CloseWhatsAppAttendance
{
    public function handle(WhatsAppAttendance $attendance, ?string $outcomeNotes = null): WhatsAppAttendance
    {
        $attendance->update([
            'status' => WhatsAppAttendance::STATUS_CLOSED,
            'ended_at' => now(),
            'outcome_notes' => $outcomeNotes,
        ]);

        return $attendance;
    }
}
