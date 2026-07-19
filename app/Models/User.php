<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is a rep.
     */
    public function isRep(): bool
    {
        return $this->role === 'rep';
    }

    /**
     * Get the leads assigned to this user.
     */
    public function leads()
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    /**
     * Get the activities logged on leads assigned to this user (Option B).
     */
    public function activitiesThroughLeads()
    {
        return $this->hasManyThrough(Activity::class, Lead::class, 'assigned_to', 'lead_id');
    }
}
