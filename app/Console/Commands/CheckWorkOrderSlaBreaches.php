<?php

namespace App\Console\Commands;

use App\Enums\SlaStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Services\SlaService;
use Illuminate\Console\Command;

/**
 * Re-evaluates sla_status for every open work order with an SLA due date.
 * Status transitions already refresh this live (WorkOrderStatusService),
 * but a ticket that just sits without any status change also needs its
 * SLA to tick from Normal → Warning → Critical → Breached over time —
 * this command is what makes that happen. Scheduled in routes/console.php;
 * the Fase 61 Scheduler phase is what formalizes the full scheduling setup.
 */
class CheckWorkOrderSlaBreaches extends Command
{
    protected $signature = 'sla:check';

    protected $description = 'Re-evaluate SLA status for open work orders and log newly detected breaches';

    public function handle(SlaService $slaService): int
    {
        $terminalStatuses = [WorkOrderStatus::Completed->value, WorkOrderStatus::Cancelled->value];

        $workOrders = WorkOrder::withoutCompanyScope()
            ->whereNotNull('sla_due_at')
            ->whereNotIn('status', $terminalStatuses)
            ->get();

        $breached = 0;

        foreach ($workOrders as $workOrder) {
            $previous = $workOrder->sla_status;
            $current = $slaService->refreshStatus($workOrder);

            if ($previous === $current) {
                continue;
            }

            $workOrder->sla_status = $current;
            $workOrder->save();

            if ($current === SlaStatus::Breached) {
                $workOrder->slaEvents()->create([
                    'event_type' => 'breached',
                    'occurred_at' => now(),
                ]);
                $breached++;
            }
        }

        $this->info("Checked {$workOrders->count()} open work orders, {$breached} newly breached.");

        return self::SUCCESS;
    }
}
