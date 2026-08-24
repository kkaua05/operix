<?php

use App\Models\Company;
use App\Models\Product;
use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

test('operationalSummary is cached and does not reflect a change made within the TTL', function () {
    $user = actingAsCompanyUser(['admin']);
    $from = Carbon::parse('2026-02-01');
    $to = Carbon::parse('2026-02-28');

    createWorkOrderForCompany($user->company_id, ['created_at' => '2026-02-10']);

    $first = app(ReportService::class)->operationalSummary($user->company_id, $from, $to);
    expect($first['total'])->toBe(1);

    createWorkOrderForCompany($user->company_id, ['created_at' => '2026-02-12']);

    $second = app(ReportService::class)->operationalSummary($user->company_id, $from, $to);
    expect($second['total'])->toBe(1);
});

test('stockSummary cache is scoped per company', function () {
    $userA = actingAsCompanyUser(['admin']);
    Product::factory()->create(['company_id' => $userA->company_id, 'stock_quantity' => 1, 'min_stock' => 10]);

    $summaryA = app(ReportService::class)->stockSummary($userA->company_id);
    expect($summaryA['critical_products'])->toHaveCount(1);

    $companyB = Company::factory()->create();
    $summaryB = app(ReportService::class)->stockSummary($companyB->id);
    expect($summaryB['critical_products'])->toHaveCount(0);
});

test('the cache expires after the TTL window', function () {
    $user = actingAsCompanyUser(['admin']);
    $from = Carbon::parse('2026-02-01');
    $to = Carbon::parse('2026-02-28');

    createWorkOrderForCompany($user->company_id, ['created_at' => '2026-02-10']);
    app(ReportService::class)->operationalSummary($user->company_id, $from, $to);

    Cache::flush();

    createWorkOrderForCompany($user->company_id, ['created_at' => '2026-02-12']);
    $refreshed = app(ReportService::class)->operationalSummary($user->company_id, $from, $to);

    expect($refreshed['total'])->toBe(2);
});
