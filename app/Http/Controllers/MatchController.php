<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Services\TournamentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    public function recordScore(GameMatch $match, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
        ]);

        $this->tournamentService->recordMatchScore(
            $match,
            $validated['home_score'],
            $validated['away_score']
        );

        return response()->json([
            'message' => 'Wynik meczu został zapisany.',
            'match' => $match->fresh(),
        ]);
    }
}
