<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchHistory extends Model
{
    protected $table = 'match_histories';

    protected $fillable = [
        'match_id',
        'tournament_id',
        'tournament_name',
        'home_team_id',
        'away_team_id',
        'home_team_name',
        'away_team_name',
        'home_score',
        'away_score',
        'winner_team_id',
        'winner_team_name',
        'round_number',
        'match_number',
        'played_at',
    ];

    protected $casts = [
        'match_id' => 'integer',
        'tournament_id' => 'integer',
        'home_team_id' => 'integer',
        'away_team_id' => 'integer',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'winner_team_id' => 'integer',
        'round_number' => 'integer',
        'match_number' => 'integer',
        'played_at' => 'datetime',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(MatchHistoryPlayer::class, 'match_history_id');
    }
}
