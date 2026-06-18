<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

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

    public function store(StoreTeamRequest $request)
    {
        $team = $this->teamService->createTeam(Auth::user(), $request->validated()['name']);

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

    public function update(UpdateTeamRequest $request, Team $team)
    {
        $this->teamService->updateTeam($team, $request->validated()['name']);

        return redirect()->route('teams.show', $team->id)->with('message', 'Drużyna została zaktualizowana.');
    }

    public function destroy(Team $team)
    {
        $this->teamService->deleteTeam($team, Auth::user());

        return redirect()->route('dashboard')->with('message', 'Drużyna została usunięta. Otrzymujesz 7 dni blokady na tworzenie nowej drużyny.');
    }

    public function leave()
    {
        $this->teamService->leaveTeam(Auth::user());

        return redirect()->route('dashboard')->with('message', 'Opuściłeś drużynę. Otrzymujesz 1 dzień blokady na dołączenie do nowej drużyny.');
    }
}