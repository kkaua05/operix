<?php

use App\Enums\FinancialTransactionType;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Services\FinancialService;
use App\Services\StockService;
use Illuminate\Support\Carbon;

test('summaryForWorkOrder combines item revenue, material cost, and manual entries', function () {
    $user = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    $workOrder->items()->create(['description' => 'Mão de obra', 'quantity' => 1, 'unit_price' => 300, 'total_price' => 300]);

    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 10, 'cost_price' => 20]);
    app(StockService::class)->consumeForWorkOrder($workOrder, $product, 2, $user);

    $workOrder->financialTransactions()->create([
        'type' => FinancialTransactionType::Cost,
        'description' => 'Deslocamento',
        'amount' => 50,
        'occurred_at' => now(),
        'created_by' => $user->id,
    ]);

    $workOrder->financialTransactions()->create([
        'type' => FinancialTransactionType::Revenue,
        'description' => 'Taxa de visita',
        'amount' => 40,
        'occurred_at' => now(),
        'created_by' => $user->id,
    ]);

    $summary = app(FinancialService::class)->summaryForWorkOrder($workOrder->fresh());

    expect($summary['revenue_items'])->toBe(300.0)
        ->and($summary['revenue_manual'])->toBe(40.0)
        ->and($summary['total_revenue'])->toBe(340.0)
        ->and($summary['cost_materials'])->toBe(40.0)
        ->and($summary['cost_manual'])->toBe(50.0)
        ->and($summary['total_cost'])->toBe(90.0)
        ->and($summary['margin'])->toBe(250.0)
        ->and(round($summary['margin_percentage'], 2))->toBe(73.53);
});

test('summaryForWorkOrder returns a zero margin percentage when there is no revenue', function () {
    $user = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    $summary = app(FinancialService::class)->summaryForWorkOrder($workOrder);

    expect($summary['total_revenue'])->toBe(0.0)
        ->and($summary['margin_percentage'])->toBe(0.0);
});

test('ledgerTotals sums revenue and cost transactions within a date range', function () {
    $user = actingAsCompanyUser(['admin']);

    FinancialTransaction::factory()->create([
        'company_id' => $user->company_id, 'type' => 'revenue', 'amount' => 100, 'occurred_at' => '2026-01-10',
    ]);
    FinancialTransaction::factory()->create([
        'company_id' => $user->company_id, 'type' => 'cost', 'amount' => 30, 'occurred_at' => '2026-01-15',
    ]);
    FinancialTransaction::factory()->create([
        'company_id' => $user->company_id, 'type' => 'revenue', 'amount' => 500, 'occurred_at' => '2026-03-01',
    ]);

    $totals = app(FinancialService::class)->ledgerTotals(
        $user->company_id,
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-31'),
    );

    expect($totals['total_revenue'])->toBe(100.0)
        ->and($totals['total_cost'])->toBe(30.0)
        ->and($totals['net'])->toBe(70.0);
});
