<?php

namespace App\Http\Controllers;

use App\Models\MatchHistory;
use App\Models\MatchHistoryPlayer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function ranking()
    {
        $teams = Team::all()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'logo_url' => $team->logo_url,
                'win_rate' => $team->getWinRate(),
                'streak' => $team->getStreak(),
                'average_points' => $team->getAveragePoints(),
            ];
        })->sortByDesc('win_rate')->values();

        $players = User::where('role', 'user')->get()->map(function ($player) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'rating' => $player->rating ?? 'N/A',
                'team_name' => $player->team ? $player->team->name : 'No Team',
                'win_rate' => $player->getWinRate(),
                'streak' => $player->getStreak(),
                'average_points' => $player->getAveragePoints(),
            ];
        })->sortByDesc('win_rate')->values();

        return Inertia::render('Stats/Ranking', [
            'teams' => $teams,
            'players' => $players,
        ]);
    }

    public function playerStats(User $user)
    {
        $stats = [
            'win_rate' => $user->getWinRate(),
            'streak' => $user->getStreak(),
            'average_points' => $user->getAveragePoints(),
        ];

        $history = MatchHistoryPlayer::where('user_id', $user->id)
            ->with('matchHistory')
            ->get()
            ->map(function ($playerRecord) {
                $historyRecord = $playerRecord->matchHistory;
                if (!$historyRecord) return null;

                return [
                    'id' => $historyRecord->id,
                    'tournament_name' => $historyRecord->tournament_name,
                    'home_team_name' => $historyRecord->home_team_name,
                    'away_team_name' => $historyRecord->away_team_name,
                    'home_score' => $historyRecord->home_score,
                    'away_score' => $historyRecord->away_score,
                    'played_at' => $historyRecord->played_at ? $historyRecord->played_at->toDateTimeString() : 'N/A',
                    'result' => ($historyRecord->winner_team_id === $playerRecord->team_id) ? 'W' : 'L',
                ];
            })->filter()->values();

        return Inertia::render('Stats/PlayerProfile', [
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'rating' => $user->rating ?? 'N/A',
                'team_name' => $user->team ? $user->team->name : 'No Team',
            ],
            'stats' => $stats,
            'history' => $history,
        ]);
    }

    public function teamStats(Team $team)
    {
        $stats = [
            'win_rate' => $team->getWinRate(),
            'streak' => $team->getStreak(),
            'average_points' => $team->getAveragePoints(),
        ];

        $history = MatchHistory::where('home_team_id', $team->id)
            ->orWhere('away_team_id', $team->id)
            ->orderBy('played_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($historyRecord) use ($team) {
                return [
                    'id' => $historyRecord->id,
                    'tournament_name' => $historyRecord->tournament_name,
                    'home_team_name' => $historyRecord->home_team_name,
                    'away_team_name' => $historyRecord->away_team_name,
                    'home_score' => $historyRecord->home_score,
                    'away_score' => $historyRecord->away_score,
                    'played_at' => $historyRecord->played_at ? $historyRecord->played_at->toDateTimeString() : 'N/A',
                    'result' => ($historyRecord->winner_team_id === $team->id) ? 'W' : 'L',
                ];
            });

        return Inertia::render('Stats/TeamProfile', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'logo_url' => $team->logo_url,
                'leader_name' => $team->leader ? $team->leader->name : 'N/A',
                'members' => $team->members->map(fn($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'rating' => $member->rating ?? 'N/A',
                    'role' => ($member->id === $team->leader_id) ? 'Leader' : 'Member',
                ]),
            ],
            'stats' => $stats,
            'history' => $history,
        ]);
    }
}
