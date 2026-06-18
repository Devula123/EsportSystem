<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'game_name', 'max_teams', 'start_date', 'status'])]
class Tournament extends Model
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'datetime',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_team');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }
}
