<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Holiday;
use App\Models\WorkOrder;
use Carbon\Carbon;

/**
 * Calculates SLA due dates and live status for work orders (spec §21).
 *
 * - calculateDueDate(): resolution_time_minutes from the work order's
 *   SlaPolicy, counted from creation. When the policy is business-hours-only,
 *   minutes only run inside config('sla.business_hours') on business days
 *   (config('sla.business_days')), skipping the company's holidays.
 * - refreshStatus()/percentageElapsed(): computed live from "now", not
 *   cached — WAITING_* statuses pause the clock, terminal statuses (§19)
 *   freeze it at their completion time.
 */
class SlaService
{
    public function calculateDueDate(WorkOrder $workOrder): ?Carbon
    {
        $policy = $workOrder->slaPolicy;

        if (! $policy) {
            return null;
        }

        $start = Carbon::parse($workOrder->created_at ?? now());

        if (! $policy->business_hours_only) {
            return $start->copy()->addMinutes($policy->resolution_time_minutes);
        }

        return $this->addBusinessMinutes($start, $policy->resolution_time_minutes, $workOrder->company_id);
    }

    public function refreshStatus(WorkOrder $workOrder): SlaStatus
    {
        if (! $workOrder->sla_due_at) {
            return SlaStatus::Normal;
        }

        if ($this->isPausedStatus($workOrder->status)) {
            return SlaStatus::Paused;
        }

        if ($this->isTerminalStatus($workOrder->status)) {
            $referenceTime = $workOrder->completed_at ?? now();

            return $referenceTime->greaterThan($workOrder->sla_due_at) ? SlaStatus::Breached : SlaStatus::Normal;
        }

        if (now()->greaterThan($workOrder->sla_due_at)) {
            return SlaStatus::Breached;
        }

        $percentage = $this->percentageElapsed($workOrder);

        return match (true) {
            $percentage >= config('sla.thresholds.critical', 90) => SlaStatus::Critical,
            $percentage >= config('sla.thresholds.warning', 70) => SlaStatus::Warning,
            default => SlaStatus::Normal,
        };
    }

    public function percentageElapsed(WorkOrder $workOrder): ?float
    {
        if (! $workOrder->sla_due_at || ! $workOrder->created_at) {
            return null;
        }

        $totalSeconds = $workOrder->created_at->diffInSeconds($workOrder->sla_due_at);

        if ($totalSeconds <= 0) {
            return 100.0;
        }

        $reference = $this->isTerminalStatus($workOrder->status)
            ? ($workOrder->completed_at ?? now())
            : now();

        $elapsedSeconds = $workOrder->created_at->diffInSeconds($reference);

        return min(100.0, round(($elapsedSeconds / $totalSeconds) * 100, 1));
    }

    protected function isPausedStatus(WorkOrderStatus $status): bool
    {
        return in_array($status, [
            WorkOrderStatus::WaitingCustomer,
            WorkOrderStatus::WaitingMaterial,
            WorkOrderStatus::WaitingApproval,
        ], true);
    }

    protected function isTerminalStatus(WorkOrderStatus $status): bool
    {
        return in_array($status, [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled], true);
    }

    protected function addBusinessMinutes(Carbon $start, int $minutes, int $companyId): Carbon
    {
        $cursor = $this->snapToBusinessWindow($start->copy(), $companyId);
        $remaining = $minutes;

        while ($remaining > 0) {
            $dayEnd = $cursor->copy()->setTimeFromTimeString($this->businessEnd());
            $availableToday = max(0, $cursor->diffInMinutes($dayEnd, false));

            if ($remaining <= $availableToday) {
                return $cursor->copy()->addMinutes($remaining);
            }

            $remaining -= $availableToday;
            $cursor = $this->startOfNextBusinessDay($cursor, $companyId);
        }

        return $cursor;
    }

    protected function snapToBusinessWindow(Carbon $moment, int $companyId): Carbon
    {
        if (! $this->isBusinessDay($moment, $companyId)) {
            return $this->startOfNextBusinessDay($moment, $companyId);
        }

        $businessStart = $moment->copy()->setTimeFromTimeString($this->businessStart());
        $businessEnd = $moment->copy()->setTimeFromTimeString($this->businessEnd());

        if ($moment->lessThan($businessStart)) {
            return $businessStart;
        }

        if ($moment->greaterThanOrEqualTo($businessEnd)) {
            return $this->startOfNextBusinessDay($moment, $companyId);
        }

        return $moment;
    }

    protected function startOfNextBusinessDay(Carbon $moment, int $companyId): Carbon
    {
        $next = $moment->copy()->addDay()->startOfDay();

        while (! $this->isBusinessDay($next, $companyId)) {
            $next = $next->addDay();
        }

        return $next->setTimeFromTimeString($this->businessStart());
    }

    protected function isBusinessDay(Carbon $moment, int $companyId): bool
    {
        if (! in_array($moment->dayOfWeek, config('sla.business_days'), true)) {
            return false;
        }

        return ! $this->isHoliday($moment, $companyId);
    }

    protected function isHoliday(Carbon $moment, int $companyId): bool
    {
        return Holiday::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($moment) {
                $query->where(function ($query) use ($moment) {
                    $query->where('is_recurring_yearly', false)
                        ->whereDate('date', $moment->toDateString());
                })->orWhere(function ($query) use ($moment) {
                    $query->where('is_recurring_yearly', true)
                        ->whereMonth('date', $moment->month)
                        ->whereDay('date', $moment->day);
                });
            })
            ->exists();
    }

    protected function businessStart(): string
    {
        return config('sla.business_hours.start', '08:00');
    }

    protected function businessEnd(): string
    {
        return config('sla.business_hours.end', '18:00');
    }
}
