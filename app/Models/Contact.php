<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'role_title',
    'email',
    'phone',
    'whatsapp',
    'is_decision_maker',
    'is_primary',
    'company_id',
    'assigned_user_id',
    'notes',
])]
class Contact extends BaseModel
{
    use SoftDeletes;

    protected $table = 'contacts';

    protected function casts(): array
    {
        return [
            'is_decision_maker' => 'boolean',
            'is_primary' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'contact_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'primary_contact_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'contact_id');
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'contact_id');
    }
}
