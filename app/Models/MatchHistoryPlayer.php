<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchHistoryPlayer extends Model
{
    protected $table = 'match_history_players';

    protected $fillable = [
        'match_history_id',
        'team_id',
        'team_name',
        'user_id',
        'username',
        'role',
    ];

    protected $casts = [
        'match_history_id' => 'integer',
        'team_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function matchHistory(): BelongsTo
    {
        return $this->belongsTo(MatchHistory::class, 'match_history_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
