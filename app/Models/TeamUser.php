<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TeamUser
 * 
 * @property int $team_id
 * @property int $user_id
 * @property string $role
 * 
 * @property Team $team
 * @property User $user
 *
 * @package App\Models
 */
class TeamUser extends Model
{
	protected $table = 'team_user';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'team_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'role'
	];

	public function team()
	{
		return $this->belongsTo(Team::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
