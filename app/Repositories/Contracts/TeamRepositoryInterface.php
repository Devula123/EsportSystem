<?php

namespace App\Repositories\Contracts;

use App\Models\Team;
use Illuminate\Support\Collection;

interface TeamRepositoryInterface
{
    public function getTeamsWithStats(): Collection;

    public function getTeamMatchHistory(Team $team): Collection;
}
