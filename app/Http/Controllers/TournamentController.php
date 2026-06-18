<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'game_name' => 'required|string|max:255',
            'max_teams' => 'required|integer|min:2',
            'start_date' => 'required|date',
        ]);

        $tournament = $this->tournamentService->proposeTournament($validated);

        return response()->json([
            'message' => 'Wniosek o utworzenie turnieju został zgłoszony.',
            'tournament' => $tournament,
        ], 201);
    }

    public function join(Tournament $tournament): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Niezalogowany użytkownik.'], 401);
        }

        $team = Team::where('leader_id', $user->id)->first();
        if (!$team) {
            return response()->json([
                'message' => 'Tylko lider drużyny może zapisać ją do turnieju.'
            ], 403);
        }

        $this->tournamentService->registerTeam($tournament, $team);

        return response()->json([
            'message' => 'Drużyna została pomyślnie zapisana do turnieju.',
        ]);
    }

    public function start(Tournament $tournament): JsonResponse
    {
        $this->tournamentService->startTournamentAndSeed($tournament);

        return response()->json([
            'message' => 'Turniej został rozpoczęty. Pierwsza runda została wygenerowana.',
            'tournament' => $tournament->fresh()->load('matches'),
        ]);
    }
}
