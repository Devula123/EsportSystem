<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TournamentTeam
 * 
 * @property int $tournament_id
 * @property int $team_id
 * 
 * @property Team $team
 * @property Tournament $tournament
 *
 * @package App\Models
 */
class TournamentTeam extends Model
{
	protected $table = 'tournament_team';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'tournament_id' => 'int',
		'team_id' => 'int'
	];

	public function team()
	{
		return $this->belongsTo(Team::class);
	}

	public function tournament()
	{
		return $this->belongsTo(Tournament::class);
	}
}
