<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use AuditableTrait, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'phone', 'avatar_url', 'role', 'is_active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
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

    public function stageHistories()
    {
        return $this->hasMany(OpportunityStageHistory::class, 'user_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'owner_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}