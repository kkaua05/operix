<?php

namespace App\Livewire\Dashboard;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\FinancialService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The executive dashboard (§55-58, replacing the Fase 1 placeholder):
 * a KPI snapshot for the current month plus an onboarding checklist for a
 * brand-new company that hasn't finished basic setup yet. Every number
 * reuses the services already built for Reports (§36) and Financial (§35)
 * — no separate aggregation logic lives here.
 */
#[Layout('components.layouts.app', ['title' => 'Dashboard — Operix'])]
class Index extends Component
{
    public function render(ReportService $reportService, FinancialService $financialService): View
    {
        $companyId = (int) auth()->user()->company_id;
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $hasTechnicians = null;
        $hasCustomers = null;
        $hasWorkOrders = null;

        if ($companyId) {
            $hasTechnicians = Technician::query()->exists();
            $hasCustomers = Customer::query()->exists();
            $hasWorkOrders = WorkOrder::query()->exists();
        }

        return view('livewire.dashboard.index', [
            'operational' => $companyId ? $reportService->operationalSummary($companyId, $from, $to) : null,
            'sla' => $companyId ? $reportService->slaSummary($companyId, $from, $to) : null,
            'financial' => $companyId ? $financialService->ledgerTotals($companyId, $from, $to) : null,
            'stock' => $companyId ? $reportService->stockSummary($companyId) : null,
            'attentionWorkOrders' => $companyId ? WorkOrder::query()
                ->whereIn('sla_status', ['warning', 'critical', 'breached'])
                ->whereNotIn('status', [WorkOrderStatus::Completed->value, WorkOrderStatus::Cancelled->value])
                ->with('customer')
                ->orderBy('sla_due_at')
                ->limit(5)
                ->get() : collect(),
            'hasTechnicians' => $hasTechnicians,
            'hasCustomers' => $hasCustomers,
            'hasWorkOrders' => $hasWorkOrders,
            'monthLabel' => Carbon::now()->translatedFormat('F/Y'),
        ]);
    }
}
