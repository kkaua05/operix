<?php

namespace App\Policies;

use App\Models\Technician;
use App\Models\User;

class TechnicianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('technicians.view');
    }

    public function view(User $user, Technician $technician): bool
    {
        return $user->can('technicians.view') && $this->sameCompany($user, $technician);
    }

    public function create(User $user): bool
    {
        return $user->can('technicians.manage');
    }

    public function update(User $user, Technician $technician): bool
    {
        return $user->can('technicians.manage') && $this->sameCompany($user, $technician);
    }

    public function delete(User $user, Technician $technician): bool
    {
        return $user->can('technicians.manage') && $this->sameCompany($user, $technician);
    }

    protected function sameCompany(User $user, Technician $technician): bool
    {
        return $user->company_id !== null && $user->company_id === $technician->company_id;
    }
}
