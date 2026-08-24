<?php

use App\Enums\WorkOrderStatus;
use App\Livewire\Dispatch\Center as DispatchCenter;
use App\Livewire\WorkOrders\Index as WorkOrderIndex;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Regression net (§47/§76): the query count for these two list pages must
 * stay flat as the row count grows — if it scales linearly with the
 * number of work orders, an eager-loaded relation got turned back into a
 * lazy one. Each test renders the same page twice, at two very different
 * row counts, and asserts the second render isn't meaningfully more
 * expensive — some one-time warmup queries (permission cache, session)
 * only fire on the very first render within a test, so this allows a
 * small fixed tolerance rather than demanding byte-for-byte equality; a
 * genuine N+1 would still blow well past it (+20 rows ≈ +20 queries).
 */
function countQueriesFor(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('the work orders list runs the same number of queries regardless of row count', function () {
    $user = actingAsCompanyUser(['admin']);

    createWorkOrderForCompany($user->company_id);
    createWorkOrderForCompany($user->company_id);
    $smallCount = countQueriesFor(fn () => Livewire::test(WorkOrderIndex::class));

    for ($i = 0; $i < 20; $i++) {
        createWorkOrderForCompany($user->company_id);
    }
    $largeCount = countQueriesFor(fn () => Livewire::test(WorkOrderIndex::class));

    expect($largeCount)->toBeLessThanOrEqual($smallCount + 3);
});

test('the dispatch center runs the same number of queries regardless of row count', function () {
    $user = actingAsCompanyUser(['dispatcher']);

    createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value]);
    Technician::factory()->create(['company_id' => $user->company_id]);
    $smallCount = countQueriesFor(fn () => Livewire::test(DispatchCenter::class));

    for ($i = 0; $i < 15; $i++) {
        createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value]);
        Technician::factory()->create(['company_id' => $user->company_id]);
    }
    $largeCount = countQueriesFor(fn () => Livewire::test(DispatchCenter::class));

    expect($largeCount)->toBeLessThanOrEqual($smallCount + 3);
});
