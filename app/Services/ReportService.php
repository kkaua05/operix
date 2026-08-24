<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Technician;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only aggregation queries backing the Reports module (§36). Every
 * method is scoped to a single company and an optional date range.
 *
 * Each result is cached for a short TTL (§47/§76): these scan every work
 * order/product in the range on every call, and a report screen re-renders
 * that query on every filter tweak within the same browsing session. A
 * couple of minutes of staleness is an acceptable trade for not re-running
 * a full aggregate scan on every render; the cache key is scoped to the
 * company and the exact params, so it never leaks across tenants or masks
 * a genuinely different query.
 */
class ReportService
{
    protected const CACHE_TTL_SECONDS = 120;

    /**
     * @return array{
     *     total: int, by_status: Collection<string, int>, by_priority: Collection<string, int>,
     *     avg_resolution_hours: float,
     * }
     */
    public function operationalSummary(int $companyId, Carbon $from, Carbon $to): array
    {
        return $this->remember('operational', $companyId, $from, $to, fn () => $this->computeOperationalSummary($companyId, $from, $to));
    }

    /**
     * @return array{
     *     total: int, by_status: Collection<string, int>, by_priority: Collection<string, int>,
     *     avg_resolution_hours: float,
     * }
     */
    protected function computeOperationalSummary(int $companyId, Carbon $from, Carbon $to): array
    {
        $workOrders = WorkOrder::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);

        $byStatus = (clone $workOrders)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byPriority = (clone $workOrders)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $completionTimes = (clone $workOrders)
            ->where('status', WorkOrderStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->get(['created_at', 'completed_at']);

        return [
            'total' => (clone $workOrders)->count(),
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'avg_resolution_hours' => $this->averageHours($completionTimes),
        ];
    }

    /**
     * @return array{total: int, breached: int, on_time_percentage: float}
     */
    public function slaSummary(int $companyId, Carbon $from, Carbon $to): array
    {
        return $this->remember('sla', $companyId, $from, $to, fn () => $this->computeSlaSummary($companyId, $from, $to));
    }

    /**
     * @return array{total: int, breached: int, on_time_percentage: float}
     */
    protected function computeSlaSummary(int $companyId, Carbon $from, Carbon $to): array
    {
        $completed = WorkOrder::query()
            ->where('company_id', $companyId)
            ->where('status', WorkOrderStatus::Completed->value)
            ->whereNotNull('sla_due_at')
            ->whereBetween('completed_at', [$from->startOfDay(), $to->endOfDay()]);

        $total = (clone $completed)->count();
        $breached = (clone $completed)->where('sla_status', SlaStatus::Breached->value)->count();

        return [
            'total' => $total,
            'breached' => $breached,
            'on_time_percentage' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100.0,
        ];
    }

    /**
     * @return Collection<int, array{
     *     technician: Technician, completed_count: int, avg_resolution_hours: float, avg_rating: float,
     * }>
     */
    public function technicianProductivity(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return $this->remember('technicians', $companyId, $from, $to, fn () => $this->computeTechnicianProductivity($companyId, $from, $to));
    }

    /**
     * @return Collection<int, array{
     *     technician: Technician, completed_count: int, avg_resolution_hours: float, avg_rating: float,
     * }>
     */
    protected function computeTechnicianProductivity(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $workOrders = WorkOrder::query()
            ->where('company_id', $companyId)
            ->where('status', WorkOrderStatus::Completed->value)
            ->whereNotNull('technician_id')
            ->whereBetween('completed_at', [$from->startOfDay(), $to->endOfDay()])
            ->get(['technician_id', 'created_at', 'completed_at'])
            ->groupBy('technician_id');

        $technicianIds = $workOrders->keys();

        $technicians = Technician::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $technicianIds)
            ->get()
            ->keyBy('id');

        $avgRatings = Rating::query()
            ->where('company_id', $companyId)
            ->whereIn('technician_id', $technicianIds)
            ->selectRaw('technician_id, AVG(score) as avg_score')
            ->groupBy('technician_id')
            ->pluck('avg_score', 'technician_id');

        $result = collect();

        foreach ($workOrders as $technicianId => $group) {
            $technician = $technicians->get($technicianId);

            if (! $technician) {
                continue;
            }

            $result->push([
                'technician' => $technician,
                'completed_count' => $group->count(),
                'avg_resolution_hours' => $this->averageHours($group),
                'avg_rating' => round((float) ($avgRatings[$technicianId] ?? 0), 1),
            ]);
        }

        return $result->sortByDesc('completed_count')->values();
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     */
    protected function averageHours(Collection $workOrders): float
    {
        if ($workOrders->isEmpty()) {
            return 0.0;
        }

        $totalHours = $workOrders->sum(
            fn (WorkOrder $workOrder) => $workOrder->created_at->diffInMinutes($workOrder->completed_at) / 60
        );

        return round($totalHours / $workOrders->count(), 1);
    }

    /**
     * @return array{critical_products: Collection<int, Product>, total_stock_value: float}
     */
    public function stockSummary(int $companyId): array
    {
        return Cache::remember(
            "reports:{$companyId}:stock",
            self::CACHE_TTL_SECONDS,
            fn () => $this->computeStockSummary($companyId),
        );
    }

    /**
     * @return array{critical_products: Collection<int, Product>, total_stock_value: float}
     */
    protected function computeStockSummary(int $companyId): array
    {
        $criticalProducts = Product::query()
            ->where('company_id', $companyId)
            ->whereColumn('stock_quantity', '<', 'min_stock')
            ->orderBy('name')
            ->get();

        $totalStockValue = (float) (Product::query()
            ->where('company_id', $companyId)
            ->selectRaw('SUM(stock_quantity * cost_price) as total')
            ->value('total') ?? 0);

        return [
            'critical_products' => $criticalProducts,
            'total_stock_value' => $totalStockValue,
        ];
    }

    /**
     * @template TValue
     *
     * @param  \Closure(): TValue  $callback
     * @return TValue
     */
    protected function remember(string $report, int $companyId, Carbon $from, Carbon $to, \Closure $callback): mixed
    {
        $key = "reports:{$companyId}:{$report}:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::remember($key, self::CACHE_TTL_SECONDS, $callback);
    }
}
