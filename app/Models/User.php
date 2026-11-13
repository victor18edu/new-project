<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'avatar_url',
    'role',
    'is_active',
    'last_login_at',
    'remember_token',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function createdActivities()
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    public function assignedActivities()
    {
        return $this->hasMany(Activity::class, 'assigned_to');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'owner_id');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'owner_id');
    }

    public function stageHistories()
    {
        return $this->hasMany(OpportunityStageHistory::class, 'user_id');
    }
}
