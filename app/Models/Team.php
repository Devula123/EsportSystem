<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_url',
        'leader_id',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(\App\Models\Match::class, 'winner_id');
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team');
    }

    public function getWinRate(): float
    {
        $histories = MatchHistory::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id)
            ->get();

        $total = $histories->count();
        if ($total === 0) {
            return 0.0;
        }

        $wins = $histories->where('winner_team_id', $this->id)->count();
        return round(($wins / $total) * 100, 1);
    }

    public function getStreak(int $limit = 5): string
    {
        $histories = MatchHistory::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id)
            ->orderBy('played_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        if ($histories->isEmpty()) {
            return 'N/A';
        }

        $streaks = [];
        foreach ($histories->reverse() as $history) {
            $streaks[] = ($history->winner_team_id === $this->id) ? 'W' : 'L';
        }

        return implode('-', $streaks);
    }

    public function getAveragePoints(): float
    {
        $histories = MatchHistory::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id)
            ->get();

        $total = $histories->count();
        if ($total === 0) {
            return 0.0;
        }

        $totalPoints = 0;
        foreach ($histories as $history) {
            if ($history->home_team_id === $this->id) {
                $totalPoints += $history->home_score;
            } else {
                $totalPoints += $history->away_score;
            }
        }

        return round($totalPoints / $total, 1);
    }
}
