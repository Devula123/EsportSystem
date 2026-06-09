<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invitation
 * 
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Team $team
 * @property User $user
 *
 * @package App\Models
 */
class Invitation extends Model
{
	protected $table = 'invitations';

	protected $casts = [
		'team_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'team_id',
		'user_id',
		'status'
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
