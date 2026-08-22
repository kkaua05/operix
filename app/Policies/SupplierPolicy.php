<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('inventory.view') && $this->sameCompany($user, $supplier);
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('inventory.manage') && $this->sameCompany($user, $supplier);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('inventory.manage') && $this->sameCompany($user, $supplier);
    }

    protected function sameCompany(User $user, Supplier $supplier): bool
    {
        return $user->company_id !== null && $user->company_id === $supplier->company_id;
    }
}
