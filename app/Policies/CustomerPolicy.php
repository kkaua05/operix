<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view') && $this->sameCompany($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.update') && $this->sameCompany($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete') && $this->sameCompany($user, $customer);
    }

    /**
     * Defense-in-depth: the BelongsToCompany global scope already prevents
     * a controller from resolving another tenant's model via route-model
     * binding, but the policy re-checks explicitly since authorization
     * must never rely solely on a query scope (spec §50).
     */
    protected function sameCompany(User $user, Customer $customer): bool
    {
        return $user->company_id !== null && $user->company_id === $customer->company_id;
    }
}
