<?php

namespace App\Repositories\Eloquent;

use App\Models\MatchHistoryPlayer;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getPlayersWithStats(): Collection
    {
        return User::where('role', 'user')
            ->with('team')
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'rating' => $player->rating ?? 'N/A',
                    'team_name' => $player->team ? $player->team->name : 'No Team',
                    'win_rate' => $player->getWinRate(),
                    'streak' => $player->getStreak(),
                    'average_points' => $player->getAveragePoints(),
                ];
            })
            ->sortByDesc('win_rate')
            ->values();
    }

    public function getPlayerMatchHistory(User $user): Collection
    {
        return MatchHistoryPlayer::where('user_id', $user->id)
            ->with('matchHistory')
            ->get()
            ->map(function ($playerRecord) {
                $historyRecord = $playerRecord->matchHistory;
                if (!$historyRecord) {
                    return null;
                }

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
            })
            ->filter()
            ->values();
    }
}
