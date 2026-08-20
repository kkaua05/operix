<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('scheduling.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('scheduling.view') && $this->sameCompany($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->can('scheduling.manage');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('scheduling.manage') && $this->sameCompany($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->can('scheduling.manage') && $this->sameCompany($user, $appointment);
    }

    protected function sameCompany(User $user, Appointment $appointment): bool
    {
        return $user->company_id !== null && $user->company_id === $appointment->company_id;
    }
}
