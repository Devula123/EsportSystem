<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Http\JsonResponse;

class AdminTournamentController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    public function index(): JsonResponse
    {
        $pendingTournaments = Tournament::where('status', 'pending_approval')->get();

        return response()->json([
            'pending_tournaments' => $pendingTournaments,
        ]);
    }

    public function approve(Tournament $tournament): JsonResponse
    {
        $this->tournamentService->approveTournament($tournament);

        return response()->json([
            'message' => 'Turniej został pomyślnie zaakceptowany.',
            'tournament' => $tournament,
        ]);
    }

    public function reject(Tournament $tournament): JsonResponse
    {
        $this->tournamentService->rejectTournament($tournament);

        return response()->json([
            'message' => 'Wniosek o turniej został odrzucony i usunięty.',
        ]);
    }
}
