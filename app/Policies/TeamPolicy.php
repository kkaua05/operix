<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('teams.view');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->can('teams.view') && $this->sameCompany($user, $team);
    }

    public function create(User $user): bool
    {
        return $user->can('teams.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can('teams.manage') && $this->sameCompany($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can('teams.manage') && $this->sameCompany($user, $team);
    }

    protected function sameCompany(User $user, Team $team): bool
    {
        return $user->company_id !== null && $user->company_id === $team->company_id;
    }
}
