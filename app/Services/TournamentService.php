<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Validation\ValidationException;

class TournamentService
{
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
    }

    public function rejectTournament(Tournament $tournament): void
    {
        if ($tournament->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => 'Tylko turnieje oczekujące na akceptację mogą zostać odrzucone.'
            ]);
        }

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

        $matchCount = $teamCount / 2;
        for ($i = 0; $i < $matchCount; $i++) {
            GameMatch::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $shuffledTeams[$i * 2]->id,
                'away_team_id' => $shuffledTeams[$i * 2 + 1]->id,
                'round_number' => 1,
                'match_number' => $i + 1,
            ]);
        }

        $tournament->update([
            'status' => 'active',
        ]);
    }
}
