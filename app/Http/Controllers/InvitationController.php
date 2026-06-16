<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvitationController extends Controller
{
    public function store(Request $request, Team $team)
    {
        if (Auth::id() !== $team->leader_id) {
            abort(403, 'Tylko lider drużyny może wysyłać zaproszenia.');
        }

        $request->validate([
            'search_key' => 'required|string',
        ]);

        $searchKey = $request->search_key;

        $userToInvite = User::where('email', $searchKey)
            ->orWhere('name', $searchKey)
            ->first();

        if (!$userToInvite) {
            return back()->withErrors([
                'search_key' => 'Nie znaleziono użytkownika o podanym adresie e-mail lub pseudonimie.'
            ]);
        }

        if ($userToInvite->id === Auth::id()) {
            return back()->withErrors([
                'search_key' => 'Nie możesz zaprosić samego siebie.'
            ]);
        }

        if ($userToInvite->team_id) {
            return back()->withErrors([
                'search_key' => 'Ten użytkownik należy już do innej drużyny.'
            ]);
        }

        $alreadyInvited = Invitation::where('team_id', $team->id)
            ->where('user_id', $userToInvite->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyInvited) {
            return back()->withErrors([
                'search_key' => 'Zaproszenie dla tego użytkownika zostało już wysłane i oczekuje na decyzję.'
            ]);
        }

        Invitation::create([
            'team_id' => $team->id,
            'user_id' => $userToInvite->id,
            'status' => 'pending',
        ]);

        return back()->with('message', 'Zaproszenie zostało pomyślnie wysłane.');
    }

    public function accept(Invitation $invitation)
    {
        $user = Auth::user();

        if ($invitation->user_id !== $user->id) {
            abort(403, 'To zaproszenie nie należy do Ciebie.');
        }

        if ($invitation->status !== 'pending') {
            return back()->withErrors([
                'error' => 'To zaproszenie nie jest już aktualne.'
            ]);
        }

        if ($user->cooldown_join_until && Carbon::now()->lessThan($user->cooldown_join_until)) {
            $hoursLeft = Carbon::now()->diffInHours($user->cooldown_join_until) + 1;
            return back()->withErrors([
                'error' => "Masz blokadę na dołączenie do nowej drużyny. Pozostało {$hoursLeft} godzin."
            ]);
        }

        if ($user->team_id) {
            return back()->withErrors([
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

        return redirect()->route('teams.show', $invitation->team_id)->with('message', 'Dołączyłeś do drużyny!');
    }

    public function decline(Invitation $invitation)
    {
        $user = Auth::user();

        if ($invitation->user_id !== $user->id) {
            abort(403, 'To zaproszenie nie należy do Ciebie.');
        }

        if ($invitation->status !== 'pending') {
            return back()->withErrors([
                'error' => 'To zaproszenie nie jest już aktualne.'
            ]);
        }

        $invitation->update([
            'status' => 'declined',
        ]);

        $invitation->delete();

        return back()->with('message', 'Zaproszenie zostało odrzucone.');
    }
}
