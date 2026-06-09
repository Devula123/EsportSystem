<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Team
 * 
 * @property int $id
 * @property string $name
 * @property string|null $logo_url
 * @property int $leader_id
 * @property Carbon|null $cooldown_create_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|Invitation[] $invitations
 * @property Collection|Match[] $matches
 * @property Collection|User[] $users
 * @property Collection|Tournament[] $tournaments
 *
 * @package App\Models
 */
class Team extends Model
{
	protected $table = 'teams';

	protected $casts = [
		'leader_id' => 'int',
		'cooldown_create_until' => 'datetime'
	];

	protected $fillable = [
		'name',
		'logo_url',
		'leader_id',
		'cooldown_create_until'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'leader_id');
	}

	public function invitations()
	{
		return $this->hasMany(Invitation::class);
	}

	public function matches()
	{
		return $this->hasMany(Match::class, 'winner_id');
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('role');
	}

	public function tournaments()
	{
		return $this->belongsToMany(Tournament::class, 'tournament_team');
	}
}
