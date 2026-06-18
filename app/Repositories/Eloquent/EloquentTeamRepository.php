<?php

namespace App\Repositories\Eloquent;

use App\Models\MatchHistory;
use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentTeamRepository implements TeamRepositoryInterface
{
    public function getTeamsWithStats(): Collection
    {
        return Team::all()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'logo_url' => $team->logo_url,
                'win_rate' => $team->getWinRate(),
                'streak' => $team->getStreak(),
                'average_points' => $team->getAveragePoints(),
            ];
        })
        ->sortByDesc('win_rate')
        ->values();
    }

    public function getTeamMatchHistory(Team $team): Collection
    {
        return MatchHistory::where('home_team_id', $team->id)
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
    }
}
