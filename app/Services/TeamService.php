<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function createTeam(User $user, string $name): Team
    {
        if ($user->team_id) {
            throw ValidationException::withMessages([
                'name' => 'Należysz już do innej drużyny.'
            ]);
        }

        $isAlreadyLeader = Team::where('leader_id', $user->id)->exists();
        if ($isAlreadyLeader) {
            throw ValidationException::withMessages([
                'name' => 'Jesteś już liderem innej drużyny.'
            ]);
        }

        if ($user->cooldown_create_until && Carbon::now()->lessThan($user->cooldown_create_until)) {
            $daysLeft = Carbon::now()->diffInDays($user->cooldown_create_until) + 1;
            throw ValidationException::withMessages([
                'name' => "Masz blokadę na tworzenie nowej drużyny jeszcze przez {$daysLeft} dni."
            ]);
        }

        $team = Team::create([
            'name' => $name,
            'leader_id' => $user->id,
        ]);

        $user->update([
            'team_id' => $team->id
        ]);

        return $team;
    }

    public function updateTeam(Team $team, string $name): void
    {
        $team->update([
            'name' => $name,
        ]);
    }

    public function deleteTeam(Team $team, User $user): void
    {
        if ($user->id !== $team->leader_id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Tylko lider drużyny może ją usunąć.');
        }

        $user->update([
            'cooldown_create_until' => Carbon::now()->addDays(7),
            'team_id' => null,
        ]);

        User::where('team_id', $team->id)->update([
            'team_id' => null
        ]);

        $team->delete();
    }

    public function leaveTeam(User $user): void
    {
        if (!$user->team_id) {
            throw ValidationException::withMessages([
                'error' => 'Nie należysz do żadnej drużyny.'
            ]);
        }

        $team = Team::find($user->team_id);
        
        if ($team && $user->id === $team->leader_id) {
            throw ValidationException::withMessages([
                'error' => 'Jako lider nie możesz opuścić drużyny. Musisz ją usunąć lub przekazać lidera.'
            ]);
        }

        $user->update([
            'team_id' => null,
            'cooldown_join_until' => Carbon::now()->addDay(),
        ]);
    }
}
