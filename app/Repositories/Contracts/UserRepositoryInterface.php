<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function getPlayersWithStats(): Collection;

    public function getPlayerMatchHistory(User $user): Collection;
}
