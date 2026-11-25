<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contact_id',
    'operator_id',
    'assigned_by_id',
    'status',
    'started_at',
    'ended_at',
    'outcome_notes',
])]
class WhatsAppAttendance extends BaseModel
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'Em andamento',
        self::STATUS_CLOSED => 'Encerrado',
        self::STATUS_TRANSFERRED => 'Transferido',
    ];

    protected $table = 'whatsapp_attendances';

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
