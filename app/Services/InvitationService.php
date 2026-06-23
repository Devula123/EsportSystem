<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use App\Notifications\InvitationAcceptedNotification;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function sendInvitation(User $leader, array $data): Invitation
    {
        $team = Team::where('leader_id', $leader->id)->first();
        if (!$team) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Tylko lider drużyny może wysyłać zaproszenia.');
        }

        $invitedUser = User::findOrFail($data['user_id']);

        if ($invitedUser->id === $leader->id) {
            throw ValidationException::withMessages([
                'user_id' => 'Nie możesz zaprosić samego siebie.'
            ]);
        }

        $alreadyInvited = Invitation::where('team_id', $team->id)
            ->where('user_id', $invitedUser->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyInvited) {
            throw ValidationException::withMessages([
                'user_id' => 'Ten użytkownik ma już aktywne zaproszenie.'
            ]);
        }

        $invitation = Invitation::create([
            'team_id' => $team->id,
            'user_id' => $invitedUser->id,
            'status' => 'pending',
        ]);

        $invitedUser->notify(new TeamInvitationNotification([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'invitation_id' => $invitation->id,
        ]));

        return $invitation;
    }

    public function acceptInvitation(Invitation $invitation, User $user): void
    {
        if ($user->id !== $invitation->user_id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Brak autoryzacji do akceptacji tego zaproszenia.');
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'To zaproszenie nie jest już aktywne.'
            ]);
        }

        $invitation->update(['status' => 'accepted']);

        $invitation->team->members()->attach($invitation->user_id, ['role' => 'player']);

        $leader = $invitation->team->leader;
        if ($leader) {
            $leader->notify(new InvitationAcceptedNotification([
                'user_id' => $invitation->user_id,
                'user_name' => $user->name,
                'team_name' => $invitation->team->name,
            ]));
        }
    }

    public function declineInvitation(Invitation $invitation, User $user): void
    {
        if ($user->id !== $invitation->user_id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Brak autoryzacji do odrzucenia tego zaproszenia.');
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'To zaproszenie nie jest już aktywne.'
            ]);
        }

        $invitation->update(['status' => 'declined']);
    }
}
