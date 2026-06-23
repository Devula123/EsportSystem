<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTournamentRequest;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Http\JsonResponse;

class TournamentController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    public function store(StoreTournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->proposeTournament($request->validated());

        return response()->json([
            'message' => 'Wniosek o utworzenie turnieju został zgłoszony.',
            'tournament' => $tournament,
        ], 201);
    }

    public function join(Tournament $tournament): JsonResponse
    {
        $this->tournamentService->registerTeamFromUser($tournament, auth()->user());

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
