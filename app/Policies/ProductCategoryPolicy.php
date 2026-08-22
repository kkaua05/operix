<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.view') && $this->sameCompany($user, $category);
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.manage') && $this->sameCompany($user, $category);
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.manage') && $this->sameCompany($user, $category);
    }

    protected function sameCompany(User $user, ProductCategory $category): bool
    {
        return $user->company_id !== null && $user->company_id === $category->company_id;
    }
}
