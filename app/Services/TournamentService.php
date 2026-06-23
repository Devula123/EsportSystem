<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\TournamentApprovedNotification;
use App\Notifications\UpcomingMatchNotification;
use Illuminate\Validation\ValidationException;

class TournamentService
{
    public function getPendingTournaments(): \Illuminate\Database\Eloquent\Collection
    {
        return Tournament::where('status', 'pending_approval')->get();
    }

    public function registerTeamFromUser(Tournament $tournament, User $user): void
    {
        $team = Team::where('leader_id', $user->id)->first();
        if (!$team) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Tylko lider drużyny może zapisać ją do turnieju.');
        }

        $this->registerTeam($tournament, $team);
    }

    public function proposeTournament(array $data): Tournament
    {
        return Tournament::create([
            'name' => $data['name'],
            'game_name' => $data['game_name'],
            'max_teams' => $data['max_teams'],
            'start_date' => $data['start_date'],
            'status' => 'pending_approval',
        ]);
    }

    public function approveTournament(Tournament $tournament): void
    {
        if ($tournament->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => 'Tylko turnieje oczekujące na akceptację mogą zostać zatwierdzone.'
            ]);
        }

        $tournament->update([
            'status' => 'ready',
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'tournament_approved',
            'description' => "Turniej " . $tournament->name . " (ID: " . $tournament->id . ") został zatwierdzony.",
            'ip_address' => request()->ip(),
        ]);

        $teamLeaders = User::whereHas('ledTeam')->get();
        foreach ($teamLeaders as $leader) {
            $leader->notify(new TournamentApprovedNotification([
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
            ]));
        }
    }

    public function rejectTournament(Tournament $tournament): void
    {
        if ($tournament->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => 'Tylko turnieje oczekujące na akceptację mogą zostać odrzucone.'
            ]);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'tournament_rejected',
            'description' => "Wniosek o turniej " . $tournament->name . " (ID: " . $tournament->id . ") został odrzucony.",
            'ip_address' => request()->ip(),
        ]);

        $tournament->delete();
    }

    public function registerTeam(Tournament $tournament, Team $team): void
    {
        if ($tournament->status !== 'ready') {
            throw ValidationException::withMessages([
                'tournament' => 'Do turnieju można się zapisać tylko, gdy jest w statusie ready.'
            ]);
        }

        $joinedTeamsCount = $tournament->teams()->count();
        if ($joinedTeamsCount >= $tournament->max_teams) {
            throw ValidationException::withMessages([
                'tournament' => 'Brak wolnych miejsc w turnieju.'
            ]);
        }

        if ($tournament->teams()->where('team_id', $team->id)->exists()) {
            throw ValidationException::withMessages([
                'team' => 'Twoja drużyna jest już zapisana do tego turnieju.'
            ]);
        }

        $tournament->teams()->attach($team->id);
    }

    public function startTournamentAndSeed(Tournament $tournament): void
    {
        if ($tournament->status !== 'ready') {
            throw ValidationException::withMessages([
                'status' => 'Turniej musi być w statusie ready, aby go rozpocząć.'
            ]);
        }

        $teams = $tournament->teams()->get();
        $teamCount = $teams->count();

        if ($teamCount !== $tournament->max_teams) {
            throw ValidationException::withMessages([
                'teams' => "Wymagana jest pełna obsada turnieju. Zapisano {$teamCount}/{$tournament->max_teams} drużyn."
            ]);
        }

        if ($teamCount < 2 || ($teamCount & ($teamCount - 1)) !== 0) {
            throw ValidationException::withMessages([
                'teams' => "Liczba drużyn musi być potęgą liczby 2 (np. 4, 8, 16)."
            ]);
        }

        $shuffledTeams = $teams->shuffle();
        $totalRounds = (int) log($teamCount, 2);

        for ($round = 1; $round <= $totalRounds; $round++) {
            $matchesInRound = $teamCount / pow(2, $round);
            for ($matchNum = 1; $matchNum <= $matchesInRound; $matchNum++) {
                $homeTeamId = null;
                $awayTeamId = null;

                if ($round === 1) {
                    $homeTeamId = $shuffledTeams[($matchNum - 1) * 2]->id;
                    $awayTeamId = $shuffledTeams[($matchNum - 1) * 2 + 1]->id;
                }

                $match = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'round_number' => $round,
                    'match_number' => $matchNum,
                ]);

                if ($round === 1) {
                    $homeTeam = Team::find($homeTeamId);
                    $awayTeam = Team::find($awayTeamId);

                    if ($homeTeam && $homeTeam->leader) {
                        $homeTeam->leader->notify(new UpcomingMatchNotification([
                            'match_id' => $match->id,
                            'tournament_id' => $tournament->id,
                            'tournament_name' => $tournament->name,
                            'opponent_name' => $awayTeam ? $awayTeam->name : 'N/A',
                            'round_number' => 1,
                        ]));
                    }

                    if ($awayTeam && $awayTeam->leader) {
                        $awayTeam->leader->notify(new UpcomingMatchNotification([
                            'match_id' => $match->id,
                            'tournament_id' => $tournament->id,
                            'tournament_name' => $tournament->name,
                            'opponent_name' => $homeTeam ? $homeTeam->name : 'N/A',
                            'round_number' => 1,
                        ]));
                    }
                }
            }
        }

        $tournament->update([
            'status' => 'active',
        ]);
    }

    public function recordMatchScore(GameMatch $match, int $homeScore, int $awayScore): void
    {
        $tournament = $match->tournament;
        if (!$tournament || $tournament->status !== 'active') {
            throw ValidationException::withMessages([
                'match' => 'Mecz must belong to an active tournament.'
            ]);
        }

        if ($homeScore === $awayScore) {
            throw ValidationException::withMessages([
                'score' => 'Mecz w drabince turniejowej nie może zakończyć się remisem.'
            ]);
        }

        $winnerId = ($homeScore > $awayScore) ? $match->home_team_id : $match->away_team_id;

        $match->update([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'winner_id' => $winnerId,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'match_score_recorded',
            'description' => "Wprowadzono wynik meczu ID: " . $match->id . " (" . ($match->homeTeam ? $match->homeTeam->name : 'Gospodarz') . " " . $homeScore . " - " . $awayScore . " " . ($match->awayTeam ? $match->awayTeam->name : 'Gość') . ") w turnieju " . $tournament->name . ".",
            'ip_address' => request()->ip(),
        ]);

        $teamCount = $tournament->max_teams;
        $totalRounds = (int) log($teamCount, 2);

        if ($match->round_number < $totalRounds) {
            $nextRound = $match->round_number + 1;
            $nextMatchNum = (int) ceil($match->match_number / 2);

            $nextMatch = GameMatch::where('tournament_id', $tournament->id)
                ->where('round_number', $nextRound)
                ->where('match_number', $nextMatchNum)
                ->first();

            if ($nextMatch) {
                if ($match->match_number % 2 !== 0) {
                    $nextMatch->update(['home_team_id' => $winnerId]);
                } else {
                    $nextMatch->update(['away_team_id' => $winnerId]);
                }
            }
        } else {
            $tournament->update([
                'status' => 'finished',
            ]);
        }
    }
}
