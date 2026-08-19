<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('equipment.view');
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $user->can('equipment.view') && $this->sameCompany($user, $equipment);
    }

    public function create(User $user): bool
    {
        return $user->can('equipment.manage');
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $user->can('equipment.manage') && $this->sameCompany($user, $equipment);
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->can('equipment.manage') && $this->sameCompany($user, $equipment);
    }

    protected function sameCompany(User $user, Equipment $equipment): bool
    {
        return $user->company_id !== null && $user->company_id === $equipment->company_id;
    }
}
