<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contact_id',
    'user_id',
    'direction',
    'status',
    'provider_message_id',
    'body',
    'sent_at',
    'metadata',
])]
class WhatsAppMessage extends Model
{
    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RECEIVED = 'received';

    /** Webhook statuses may arrive out of order; only higher ranks are applied. */
    public const STATUS_RANK = [
        self::STATUS_RECEIVED => 0,
        self::STATUS_QUEUED => 0,
        self::STATUS_SENT => 1,
        self::STATUS_DELIVERED => 2,
        self::STATUS_READ => 3,
        self::STATUS_FAILED => 4,
    ];

    /**
     * Message history is an append-only log. It intentionally does not extend
     * BaseModel so message bodies are not duplicated into the audit trail.
     */
    protected $table = 'whatsapp_messages';

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
