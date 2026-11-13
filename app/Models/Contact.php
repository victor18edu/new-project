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
    'notes',
])]
class Contact extends BaseModel
{
    protected $table = 'contacts';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function primaryOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'primary_contact_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'contact_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'primary_contact_id');
    }
}
