<?php

namespace App\Services;

use App\Enums\FinancialTransactionType;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes cost/revenue/margin for a work order (§35). Nothing here is
 * stored redundantly: item totals (billed work) and material costs (stock
 * consumed, §34) are read live from their own tables, and only ad-hoc
 * entries (e.g. "deslocamento", "comissão") live in financial_transactions.
 * This keeps the numbers always consistent with the OS's actual items and
 * materials instead of drifting from a cached snapshot.
 */
class FinancialService
{
    /**
     * @return array{
     *     revenue_items: float, revenue_manual: float, total_revenue: float,
     *     cost_materials: float, cost_manual: float, total_cost: float,
     *     margin: float, margin_percentage: float,
     * }
     */
    public function summaryForWorkOrder(WorkOrder $workOrder): array
    {
        $revenueItems = (float) $workOrder->items()->sum('total_price');
        $costMaterials = (float) $workOrder->materials()->sum('total_cost');

        $revenueManual = (float) $workOrder->financialTransactions()
            ->where('type', FinancialTransactionType::Revenue)
            ->sum('amount');

        $costManual = (float) $workOrder->financialTransactions()
            ->where('type', FinancialTransactionType::Cost)
            ->sum('amount');

        $totalRevenue = $revenueItems + $revenueManual;
        $totalCost = $costMaterials + $costManual;
        $margin = $totalRevenue - $totalCost;

        return [
            'revenue_items' => $revenueItems,
            'revenue_manual' => $revenueManual,
            'total_revenue' => $totalRevenue,
            'cost_materials' => $costMaterials,
            'cost_manual' => $costManual,
            'total_cost' => $totalCost,
            'margin' => $margin,
            'margin_percentage' => $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0.0,
        ];
    }

    /**
     * Company-wide ledger totals for the manual financial_transactions
     * entries within an optional date range, used by the Financial index.
     *
     * @return array{total_revenue: float, total_cost: float, net: float}
     */
    public function ledgerTotals(int $companyId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = DB::table('financial_transactions')
            ->where('company_id', $companyId)
            ->when($from, fn ($query) => $query->whereDate('occurred_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('occurred_at', '<=', $to));

        $totalRevenue = (float) (clone $query)->where('type', FinancialTransactionType::Revenue->value)->sum('amount');
        $totalCost = (float) (clone $query)->where('type', FinancialTransactionType::Cost->value)->sum('amount');

        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'net' => $totalRevenue - $totalCost,
        ];
    }
}
