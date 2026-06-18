<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    protected TeamRepositoryInterface $teamRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        TeamRepositoryInterface $teamRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->teamRepository = $teamRepository;
        $this->userRepository = $userRepository;
    }

    public function ranking()
    {
        $teams = $this->teamRepository->getTeamsWithStats();
        $players = $this->userRepository->getPlayersWithStats();

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

        $history = $this->userRepository->getPlayerMatchHistory($user);

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

        $history = $this->teamRepository->getTeamMatchHistory($team);

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
