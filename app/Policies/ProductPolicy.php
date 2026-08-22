<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('inventory.view') && $this->sameCompany($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('inventory.manage') && $this->sameCompany($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('inventory.manage') && $this->sameCompany($user, $product);
    }

    protected function sameCompany(User $user, Product $product): bool
    {
        return $user->company_id !== null && $user->company_id === $product->company_id;
    }
}
