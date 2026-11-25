<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'message_id',
    'editor_id',
    'old_body',
    'new_body',
])]
class WhatsAppMessageRevision extends Model
{
    protected $table = 'whatsapp_message_revisions';

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'message_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
