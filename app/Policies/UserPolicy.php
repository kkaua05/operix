<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.manage') && $this->sameCompany($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.manage') && $this->sameCompany($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.manage')
            && $this->sameCompany($user, $target)
            && $user->id !== $target->id;
    }

    protected function sameCompany(User $user, User $target): bool
    {
        return $user->company_id !== null && $user->company_id === $target->company_id;
    }
}
