<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use Illuminate\Support\Facades\DB;

final class StartWhatsAppAttendance
{
    public function handle(Contact $contact, User $operator, ?User $assignedBy = null): WhatsAppAttendance
    {
        return DB::transaction(function () use ($contact, $operator, $assignedBy): WhatsAppAttendance {
            $contact->whatsappAttendances()->open()->each(function (WhatsAppAttendance $attendance): void {
                $attendance->update([
                    'status' => WhatsAppAttendance::STATUS_TRANSFERRED,
                    'ended_at' => now(),
                ]);
            });

            $contact->forceFill(['assigned_user_id' => $operator->id])->save();

            return $contact->whatsappAttendances()->create([
                'operator_id' => $operator->id,
                'assigned_by_id' => $assignedBy?->id,
                'status' => WhatsAppAttendance::STATUS_OPEN,
                'started_at' => now(),
            ]);
        });
    }
}
