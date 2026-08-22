<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financial.view');
    }

    public function view(User $user, FinancialTransaction $transaction): bool
    {
        return $user->can('financial.view') && $this->sameCompany($user, $transaction);
    }

    public function create(User $user): bool
    {
        return $user->can('financial.manage');
    }

    public function delete(User $user, FinancialTransaction $transaction): bool
    {
        return $user->can('financial.manage') && $this->sameCompany($user, $transaction);
    }

    protected function sameCompany(User $user, FinancialTransaction $transaction): bool
    {
        return $user->company_id !== null && $user->company_id === $transaction->company_id;
    }
}
