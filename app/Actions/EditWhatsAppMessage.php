<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;

final class EditWhatsAppMessage
{
    public function handle(WhatsAppMessage $message, string $body, User $editor): WhatsAppMessage
    {
        return DB::transaction(function () use ($message, $body, $editor): WhatsAppMessage {
            $message->revisions()->create([
                'editor_id' => $editor->id,
                'old_body' => $message->body,
                'new_body' => $body,
            ]);

            $message->forceFill([
                'body' => $body,
                'edited_by' => $editor->id,
                'edited_at' => now(),
            ])->save();

            return $message;
        });
    }
}
