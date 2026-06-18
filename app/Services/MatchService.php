<?php

namespace App\Services;

use App\Models\Match;
use App\Models\MatchHistory;
use App\Models\MatchHistoryPlayer;
use App\Models\Team;
use Exception;
use Illuminate\Support\Facades\DB;

class MatchService
{
    public function recordResult(Match $match, int $homeScore, int $awayScore): MatchHistory
    {
        if ($homeScore === $awayScore) {
            throw new Exception("Single elimination matches cannot end in a draw.");
        }

        if (!$match->home_team_id || !$match->away_team_id) {
            throw new Exception("Cannot record result for a match with unassigned teams.");
        }

        return DB::transaction(function () use ($match, $homeScore, $awayScore) {
            $winnerId = ($homeScore > $awayScore) ? $match->home_team_id : $match->away_team_id;

            $match->home_score = $homeScore;
            $match->away_score = $awayScore;
            $match->winner_id = $winnerId;
            $match->save();

            $homeTeam = Team::find($match->home_team_id);
            $awayTeam = Team::find($match->away_team_id);
            $winnerTeam = Team::find($winnerId);
            $tournamentName = $match->tournament ? $match->tournament->name : 'N/A';

            $history = MatchHistory::create([
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'tournament_name' => $tournamentName,
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'home_team_name' => $homeTeam ? $homeTeam->name : 'Deleted Team',
                'away_team_name' => $awayTeam ? $awayTeam->name : 'Deleted Team',
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'winner_team_id' => $winnerId,
                'winner_team_name' => $winnerTeam ? $winnerTeam->name : 'Deleted Team',
                'round_number' => $match->round_number,
                'match_number' => $match->match_number,
                'played_at' => now(),
            ]);

            if ($homeTeam) {
                $this->archiveRoster($history->id, $homeTeam);
            }
            if ($awayTeam) {
                $this->archiveRoster($history->id, $awayTeam);
            }

            $this->advanceWinner($match, $winnerId);

            return $history;
        });
    }

    protected function archiveRoster(int $historyId, Team $team): void
    {
        if ($team->leader) {
            MatchHistoryPlayer::create([
                'match_history_id' => $historyId,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'user_id' => $team->leader->id,
                'username' => $team->leader->name,
                'role' => 'leader',
            ]);
        }

        $members = $team->members()->where('id', '!=', $team->leader_id)->get();
        foreach ($members as $member) {
            MatchHistoryPlayer::create([
                'match_history_id' => $historyId,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'user_id' => $member->id,
                'username' => $member->name,
                'role' => 'member',
            ]);
        }
    }

    protected function advanceWinner(Match $match, int $winnerId): void
    {
        $nextRound = $match->round_number + 1;
        $nextMatchNumber = (int) ceil($match->match_number / 2);

        $nextMatch = Match::where('tournament_id', $match->tournament_id)
            ->where('round_number', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();

        if ($nextMatch) {
            if ($match->match_number % 2 !== 0) {
                $nextMatch->home_team_id = $winnerId;
            } else {
                $nextMatch->away_team_id = $winnerId;
            }
            $nextMatch->save();
        } else {
            $tournament = $match->tournament;
            if ($tournament) {
                $unfinishedMatches = Match::where('tournament_id', $tournament->id)
                    ->whereNull('winner_id')
                    ->count();

                if ($unfinishedMatches === 0) {
                    $tournament->status = 'finished';
                    $tournament->save();
                }
            }
        }
    }
}
