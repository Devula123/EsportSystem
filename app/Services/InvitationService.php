<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function invite(Team $team, User $sender, string $searchKey): Invitation
    {
        if ($sender->id !== $team->leader_id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Tylko lider drużyny może wysyłać zaproszenia.');
        }

        $userToInvite = User::where('email', $searchKey)
            ->orWhere('name', $searchKey)
            ->first();

        if (!$userToInvite) {
            throw ValidationException::withMessages([
                'search_key' => 'Nie znaleziono użytkownika o podanym adresie e-mail lub pseudonimie.'
            ]);
        }

        if ($userToInvite->id === $sender->id) {
            throw ValidationException::withMessages([
                'search_key' => 'Nie możesz zaprosić samego siebie.'
            ]);
        }

        if ($userToInvite->team_id) {
            throw ValidationException::withMessages([
                'search_key' => 'Ten użytkownik należy już do innej drużyny.'
            ]);
        }

        $alreadyInvited = Invitation::where('team_id', $team->id)
            ->where('user_id', $userToInvite->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyInvited) {
            throw ValidationException::withMessages([
                'search_key' => 'Zaproszenie dla tego użytkownika zostało już wysłane i oczekuje na decyzję.'
            ]);
        }

        return Invitation::create([
            'team_id' => $team->id,
            'user_id' => $userToInvite->id,
            'status' => 'pending',
        ]);
    }

    public function acceptInvitation(Invitation $invitation, User $user): void
    {
        if ($invitation->user_id !== $user->id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('To zaproszenie nie należy do Ciebie.');
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'error' => 'To zaproszenie nie jest już aktualne.'
            ]);
        }

        if ($user->cooldown_join_until && Carbon::now()->lessThan($user->cooldown_join_until)) {
            $hoursLeft = Carbon::now()->diffInHours($user->cooldown_join_until) + 1;
            throw ValidationException::withMessages([
                'error' => "Masz blokadę na dołączenie do nowej drużyny. Pozostało {$hoursLeft} godzin."
            ]);
        }

        if ($user->team_id) {
            throw ValidationException::withMessages([
                'error' => 'Należysz już do innej drużyny. Opuść ją zanim dołączysz do nowej.'
            ]);
        }

        $user->update([
            'team_id' => $invitation->team_id,
        ]);

        $invitation->update([
            'status' => 'accepted',
        ]);

        Invitation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->delete();
    }

    public function declineInvitation(Invitation $invitation, User $user): void
    {
        if ($invitation->user_id !== $user->id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('To zaproszenie nie należy do Ciebie.');
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'error' => 'To zaproszenie nie jest już aktualne.'
            ]);
        }

        $invitation->update([
            'status' => 'declined',
        ]);

        $invitation->delete();
    }
}
