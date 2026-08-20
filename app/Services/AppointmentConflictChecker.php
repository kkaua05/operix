<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Prevents double-booking a technician or team (spec §24: "Evitar
 * conflitos de agenda"). Two appointments conflict when their time
 * windows overlap and they share the same technician or team — a
 * cancelled or no-show appointment never blocks a new booking.
 */
class AppointmentConflictChecker
{
    /**
     * @return Collection<int, Appointment>
     */
    public function conflictingAppointments(
        int $companyId,
        ?int $technicianId,
        ?int $teamId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $excludeAppointmentId = null,
    ): Collection {
        if (! $technicianId && ! $teamId) {
            return collect();
        }

        return Appointment::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($technicianId, $teamId) {
                $query->when($technicianId, fn ($q) => $q->orWhere('technician_id', $technicianId))
                    ->when($teamId, fn ($q) => $q->orWhere('team_id', $teamId));
            })
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::NoShow->value])
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end', '>', $start)
            ->when($excludeAppointmentId, fn ($q) => $q->whereKeyNot($excludeAppointmentId))
            ->with(['workOrder.customer', 'technician', 'team'])
            ->get();
    }

    public function hasConflict(
        int $companyId,
        ?int $technicianId,
        ?int $teamId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $excludeAppointmentId = null,
    ): bool {
        return $this->conflictingAppointments($companyId, $technicianId, $teamId, $start, $end, $excludeAppointmentId)->isNotEmpty();
    }
}
