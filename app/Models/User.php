<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'team_id', 'rating', 'cooldown_join_until', 'cooldown_create_until'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function ledTeam(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Team::class, 'leader_id');
    }

    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'cooldown_join_until' => 'datetime',
            'cooldown_create_until' => 'datetime',
        ];
    }
}
