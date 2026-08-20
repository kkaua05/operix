<?php

use App\Enums\WorkOrderStatus;
use App\Livewire\WorkOrders\Show;
use App\Models\Customer;
use App\Models\WorkOrder;
use Livewire\Livewire;

test('a user can view a work order from their own company', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    Livewire::test(Show::class, ['workOrder' => $workOrder])
        ->assertSee('OS-00001')
        ->assertOk();
});

test('a user cannot view a work order from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignOrder = WorkOrder::factory()->create();
    $foreignOrder = WorkOrder::withoutCompanyScope()->find($foreignOrder->id);

    Livewire::test(Show::class, ['workOrder' => $foreignOrder])->assertForbidden();
});

test('the show route 404s for a work order from another company via route model binding', function () {
    actingAsCompanyUser(['admin']);

    $foreignOrder = WorkOrder::factory()->create();

    $this->get(route('work-orders.show', $foreignOrder))->assertNotFound();
});

test('a user with work_orders.assign permission can move a work order to assigned', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Scheduled->value,
    ]);

    Livewire::test(Show::class, ['workOrder' => $workOrder])
        ->call('transitionTo', WorkOrderStatus::Assigned->value)
        ->assertHasNoErrors();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Assigned);
});

test('a user without work_orders.assign permission cannot move a work order to assigned', function () {
    $user = actingAsCompanyUser(['financial']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Scheduled->value,
    ]);

    Livewire::test(Show::class, ['workOrder' => $workOrder])
        ->call('transitionTo', WorkOrderStatus::Assigned->value)
        ->assertForbidden();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Scheduled);
});

test('a technician can start and complete their own work order', function () {
    $user = actingAsCompanyUser(['technician']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::EnRoute->value,
    ]);

    Livewire::test(Show::class, ['workOrder' => $workOrder])
        ->call('transitionTo', WorkOrderStatus::InProgress->value)
        ->assertHasNoErrors();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::InProgress)
        ->and($workOrder->fresh()->started_at)->not->toBeNull();
});

test('an invalid transition is rejected with a friendly error instead of a raw exception', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Completed->value,
    ]);

    Livewire::test(Show::class, ['workOrder' => $workOrder])
        ->call('transitionTo', WorkOrderStatus::InProgress->value)
        ->assertHasErrors('status');

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Completed);
});

test('the timeline tab shows recorded status changes', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);
    $workOrder->statusHistory()->create(['from_status' => null, 'to_status' => 'new']);

    Livewire::test(Show::class, ['workOrder' => $workOrder])
        ->call('setTab', 'timeline')
        ->assertSee('OS criada');
});
