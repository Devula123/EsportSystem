<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Match
 * 
 * @property int $id
 * @property int $tournament_id
 * @property int|null $home_team_id
 * @property int|null $away_team_id
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int $round_number
 * @property int $match_number
 * @property int|null $winner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Team|null $team
 * @property Tournament $tournament
 *
 * @package App\Models
 */
class Match extends Model
{
	protected $table = 'matches';

	protected $casts = [
		'tournament_id' => 'int',
		'home_team_id' => 'int',
		'away_team_id' => 'int',
		'home_score' => 'int',
		'away_score' => 'int',
		'round_number' => 'int',
		'match_number' => 'int',
		'winner_id' => 'int'
	];

	protected $fillable = [
		'tournament_id',
		'home_team_id',
		'away_team_id',
		'home_score',
		'away_score',
		'round_number',
		'match_number',
		'winner_id'
	];

	public function team()
	{
		return $this->belongsTo(Team::class, 'winner_id');
	}

	public function homeTeam()
	{
		return $this->belongsTo(Team::class, 'home_team_id');
	}

	public function awayTeam()
	{
		return $this->belongsTo(Team::class, 'away_team_id');
	}

	public function winner()
	{
		return $this->belongsTo(Team::class, 'winner_id');
	}

	public function tournament()
	{
		return $this->belongsTo(Tournament::class);
	}
}
