<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('work_orders.view');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('work_orders.view') && $this->sameCompany($user, $workOrder);
    }

    public function create(User $user): bool
    {
        return $user->can('work_orders.create');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('work_orders.update') && $this->sameCompany($user, $workOrder);
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('work_orders.delete') && $this->sameCompany($user, $workOrder);
    }

    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('work_orders.assign') && $this->sameCompany($user, $workOrder);
    }

    public function start(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('work_orders.start') && $this->sameCompany($user, $workOrder);
    }

    public function complete(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('work_orders.complete') && $this->sameCompany($user, $workOrder);
    }

    protected function sameCompany(User $user, WorkOrder $workOrder): bool
    {
        return $user->company_id !== null && $user->company_id === $workOrder->company_id;
    }
}
