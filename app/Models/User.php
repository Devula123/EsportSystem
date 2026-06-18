<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'team_id', 'rating', 'cooldown_join_until', 'cooldown_create_until', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
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

    public function getWinRate(): float
    {
        $players = MatchHistoryPlayer::where('user_id', $this->id)->get();
        $total = $players->count();
        if ($total === 0) {
            return 0.0;
        }

        $wins = 0;
        foreach ($players as $player) {
            $history = $player->matchHistory;
            if ($history && $history->winner_team_id === $player->team_id) {
                $wins++;
            }
        }

        return round(($wins / $total) * 100, 1);
    }

    public function getStreak(int $limit = 5): string
    {
        $players = MatchHistoryPlayer::where('user_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        if ($players->isEmpty()) {
            return 'N/A';
        }

        $streaks = [];
        foreach ($players->reverse() as $player) {
            $history = $player->matchHistory;
            if ($history) {
                $streaks[] = ($history->winner_team_id === $player->team_id) ? 'W' : 'L';
            }
        }

        return implode('-', $streaks);
    }

    public function getAveragePoints(): float
    {
        $players = MatchHistoryPlayer::where('user_id', $this->id)->get();
        $total = $players->count();
        if ($total === 0) {
            return 0.0;
        }

        $totalPoints = 0;
        $validMatches = 0;
        foreach ($players as $player) {
            $history = $player->matchHistory;
            if ($history) {
                $validMatches++;
                if ($history->home_team_id === $player->team_id) {
                    $totalPoints += $history->home_score;
                } else {
                    $totalPoints += $history->away_score;
                }
            }
        }

        if ($validMatches === 0) {
            return 0.0;
        }

        return round($totalPoints / $validMatches, 1);
    }
}
