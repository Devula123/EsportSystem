<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tournament
 * 
 * @property int $id
 * @property string $name
 * @property string $game_name
 * @property int $max_teams
 * @property Carbon|null $start_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Match[] $matches
 * @property Collection|Team[] $teams
 *
 * @package App\Models
 */
class Tournament extends Model
{
	protected $table = 'tournaments';

	protected $casts = [
		'max_teams' => 'int',
		'start_date' => 'datetime'
	];

	protected $fillable = [
		'name',
		'game_name',
		'max_teams',
		'start_date',
		'status'
	];

	public function matches()
	{
		return $this->hasMany(Match::class);
	}

	public function teams()
	{
		return $this->belongsToMany(Team::class, 'tournament_team');
	}
}
