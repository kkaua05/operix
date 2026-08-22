<?php

use App\Enums\SlaStatus;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Technician;
use App\Services\ReportService;
use Illuminate\Support\Carbon;

test('operationalSummary counts work orders by status and priority within the period', function () {
    $user = actingAsCompanyUser(['admin']);

    createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value, 'priority' => WorkOrderPriority::High->value, 'created_at' => '2026-02-10']);
    createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value, 'priority' => WorkOrderPriority::Low->value, 'created_at' => '2026-02-12']);
    createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::Completed->value, 'priority' => WorkOrderPriority::Medium->value,
        'created_at' => '2026-02-01 08:00:00', 'completed_at' => '2026-02-01 18:00:00',
    ]);
    // Outside the period entirely.
    createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value, 'created_at' => '2026-03-15']);

    $summary = app(ReportService::class)->operationalSummary(
        $user->company_id, Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28')
    );

    expect($summary['total'])->toBe(3)
        ->and($summary['by_status'][WorkOrderStatus::New->value])->toBe(2)
        ->and($summary['by_status'][WorkOrderStatus::Completed->value])->toBe(1)
        ->and($summary['by_priority'][WorkOrderPriority::High->value])->toBe(1)
        ->and($summary['avg_resolution_hours'])->toBe(10.0);
});

test('slaSummary computes the on-time percentage from completed work orders', function () {
    $user = actingAsCompanyUser(['admin']);

    createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::Completed->value, 'sla_status' => SlaStatus::Normal->value,
        'sla_due_at' => now(), 'completed_at' => '2026-02-05',
    ]);
    createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::Completed->value, 'sla_status' => SlaStatus::Breached->value,
        'sla_due_at' => now(), 'completed_at' => '2026-02-10',
    ]);
    // No SLA policy applied — must be excluded.
    createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::Completed->value, 'sla_due_at' => null, 'completed_at' => '2026-02-15',
    ]);

    $summary = app(ReportService::class)->slaSummary(
        $user->company_id, Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28')
    );

    expect($summary['total'])->toBe(2)
        ->and($summary['breached'])->toBe(1)
        ->and($summary['on_time_percentage'])->toBe(50.0);
});

test('slaSummary returns 100% on-time when there are no completed work orders', function () {
    $user = actingAsCompanyUser(['admin']);

    $summary = app(ReportService::class)->slaSummary($user->company_id, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

    expect($summary['total'])->toBe(0)
        ->and($summary['on_time_percentage'])->toBe(100.0);
});

test('technicianProductivity aggregates completed work orders and average ratings per technician', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    $wo1 = createWorkOrderForCompany($user->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Completed->value,
        'created_at' => '2026-02-01 08:00:00', 'completed_at' => '2026-02-01 12:00:00',
    ]);
    $wo2 = createWorkOrderForCompany($user->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Completed->value,
        'created_at' => '2026-02-05 08:00:00', 'completed_at' => '2026-02-05 16:00:00',
    ]);

    Rating::create(['company_id' => $user->company_id, 'work_order_id' => $wo1->id, 'customer_id' => $wo1->customer_id, 'technician_id' => $technician->id, 'score' => 5]);
    Rating::create(['company_id' => $user->company_id, 'work_order_id' => $wo2->id, 'customer_id' => $wo2->customer_id, 'technician_id' => $technician->id, 'score' => 3]);

    $rows = app(ReportService::class)->technicianProductivity(
        $user->company_id, Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28')
    );

    expect($rows)->toHaveCount(1);
    $row = $rows->first();
    expect($row['technician']->id)->toBe($technician->id)
        ->and($row['completed_count'])->toBe(2)
        ->and($row['avg_resolution_hours'])->toBe(6.0)
        ->and($row['avg_rating'])->toBe(4.0);
});

test('stockSummary lists only products below their minimum and totals the inventory value', function () {
    $user = actingAsCompanyUser(['admin']);

    Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 5, 'min_stock' => 20, 'cost_price' => 10]);
    Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 50, 'min_stock' => 10, 'cost_price' => 2]);

    $summary = app(ReportService::class)->stockSummary($user->company_id);

    expect($summary['critical_products'])->toHaveCount(1)
        ->and($summary['total_stock_value'])->toBe(150.0);
});
