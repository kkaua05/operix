<?php

namespace App\Console\Commands;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Notifications\PendingRatingReminderNotification;
use Illuminate\Console\Command;

/**
 * Reminds the assigned technician (§38 "OS concluída → avaliação") when a
 * work order has been finished for over 24h without a customer rating —
 * the common case is the rating gets collected on the spot in the
 * Technician Portal (§31), this catches the ones that slipped through.
 * Scheduled once a day; a still-unrated OS is reminded again on every run
 * until a rating is registered, since there's no snooze/dedup state to track.
 */
class RemindPendingRatings extends Command
{
    protected $signature = 'ratings:remind-pending';

    protected $description = 'Remind technicians about completed work orders still missing a customer rating';

    public function handle(): int
    {
        $workOrders = WorkOrder::withoutCompanyScope()
            ->whereIn('status', [WorkOrderStatus::Resolved->value, WorkOrderStatus::Completed->value])
            ->whereDoesntHave('rating')
            ->whereNotNull('technician_id')
            ->where('updated_at', '<=', now()->subDay())
            ->with('technician.user')
            ->get();

        $reminded = 0;

        foreach ($workOrders as $workOrder) {
            $user = $workOrder->technician?->user;

            if ($user === null) {
                continue;
            }

            $user->notify(new PendingRatingReminderNotification($workOrder));
            $reminded++;
        }

        $this->info("Reminded {$reminded} technician(s) about pending ratings.");

        return self::SUCCESS;
    }
}
