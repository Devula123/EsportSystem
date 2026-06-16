<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function show(Team $team)
    {
        $team->load(['members', 'leader', 'invitations.user']);
        
        return Inertia::render('Teams/Show', [
            'team' => $team,
            'isLeader' => Auth::id() === $team->leader_id,
            'currentUser' => Auth::user(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Teams/Create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
        ]);

        if ($user->team_id) {
            return back()->withErrors([
                'name' => 'Należysz już do innej drużyny.'
            ]);
        }

        $isAlreadyLeader = Team::where('leader_id', $user->id)->exists();
        if ($isAlreadyLeader) {
            return back()->withErrors([
                'name' => 'Jesteś już liderem innej drużyny.'
            ]);
        }

        if ($user->cooldown_create_until && Carbon::now()->lessThan($user->cooldown_create_until)) {
            $daysLeft = Carbon::now()->diffInDays($user->cooldown_create_until) + 1;
            return back()->withErrors([
                'name' => "Masz blokadę na tworzenie nowej drużyny jeszcze przez {$daysLeft} dni."
            ]);
        }

        $team = Team::create([
            'name' => $request->name,
            'leader_id' => $user->id,
        ]);

        $user->update([
            'team_id' => $team->id
        ]);

        return redirect()->route('teams.show', $team->id)->with('message', 'Drużyna została pomyślnie utworzona!');
    }

    public function edit(Team $team)
    {
        if (Auth::id() !== $team->leader_id) {
            abort(403, 'Tylko lider drużyny może ją edytować.');
        }

        return Inertia::render('Teams/Edit', [
            'team' => $team
        ]);
    }

    public function update(Request $request, Team $team)
    {
        if (Auth::id() !== $team->leader_id) {
            abort(403, 'Tylko lider drużyny może ją edytować.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
        ]);

        $team->update([
            'name' => $request->name,
        ]);

        return redirect()->route('teams.show', $team->id)->with('message', 'Drużyna została zaktualizowana.');
    }

    public function destroy(Team $team)
    {
        $user = Auth::user();

        if ($user->id !== $team->leader_id) {
            abort(403, 'Tylko lider drużyny może ją usunąć.');
        }

        $user->update([
            'cooldown_create_until' => Carbon::now()->addDays(7),
            'team_id' => null,
        ]);

        User::where('team_id', $team->id)->update([
            'team_id' => null
        ]);

        $team->delete();

        return redirect()->route('dashboard')->with('message', 'Drużyna została usunięta. Otrzymujesz 7 dni blokady na tworzenie nowej drużyny.');
    }

    public function leave()
    {
        $user = Auth::user();

        if (!$user->team_id) {
            return back()->withErrors([
                'error' => 'Nie należysz do żadnej drużyny.'
            ]);
        }

        $team = Team::find($user->team_id);
        
        if ($team && $user->id === $team->leader_id) {
            return back()->withErrors([
                'error' => 'Jako lider nie możesz opuścić drużyny. Musisz ją usunąć lub przekazać lidera.'
            ]);
        }

        $user->update([
            'team_id' => null,
            'cooldown_join_until' => Carbon::now()->addDay(),
        ]);

        return redirect()->route('dashboard')->with('message', 'Opuściłeś drużynę. Otrzymujesz 1 dzień blokady na dołączenie do nowej drużyny.');
    }
}