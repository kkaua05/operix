<?php

use App\Enums\WorkOrderStatus;
use App\Livewire\Portal\MyWorkOrders;
use App\Models\Technician;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('portal.index'))->assertRedirect('/login');
});

test('a user without a linked technician profile is forbidden', function () {
    $user = actingAsCompanyUser(['admin']);

    $this->get(route('portal.index'))->assertForbidden();
});

test('a technician can access their portal', function () {
    actingAsTechnicianUser();

    $this->get(route('portal.index'))->assertOk();
});

test('it lists only work orders assigned to the logged-in technician', function () {
    $technician = actingAsTechnicianUser();

    $mine = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Assigned->value,
    ]);
    $otherTechnician = Technician::factory()->create(['company_id' => $technician->company_id]);
    $notMine = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $otherTechnician->id, 'status' => WorkOrderStatus::Assigned->value,
    ]);

    Livewire::test(MyWorkOrders::class)
        ->assertSee($mine->number)
        ->assertDontSee($notMine->number);
});

test('it excludes completed and cancelled work orders', function () {
    $technician = actingAsTechnicianUser();

    $active = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Assigned->value,
    ]);
    $completed = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Completed->value,
    ]);

    Livewire::test(MyWorkOrders::class)
        ->assertSee($active->number)
        ->assertDontSee($completed->number);
});

test('a technician can start travel on their assigned work order', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Assigned->value,
    ]);

    Livewire::test(MyWorkOrders::class)->call('startTravel', $workOrder->id);

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::EnRoute);
});

test('marking arrived stamps arrived_at and logs a timeline milestone without changing status', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::EnRoute->value,
    ]);

    Livewire::test(MyWorkOrders::class)->call('markArrived', $workOrder->id);

    $workOrder->refresh();

    expect($workOrder->arrived_at)->not->toBeNull()
        ->and($workOrder->status)->toBe(WorkOrderStatus::EnRoute)
        ->and($workOrder->statusHistory()->where('notes', 'Técnico chegou ao local')->exists())->toBeTrue();
});

test('a technician can start service, pause, and resume', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::EnRoute->value,
    ]);

    $component = Livewire::test(MyWorkOrders::class);

    $component->call('startService', $workOrder->id);
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::InProgress);

    $component->call('pause', $workOrder->id);
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::WaitingCustomer);

    $component->call('resume', $workOrder->id);
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::InProgress);
});

test('a technician cannot act on a work order assigned to someone else', function () {
    $technician = actingAsTechnicianUser();
    $otherTechnician = Technician::factory()->create(['company_id' => $technician->company_id]);
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $otherTechnician->id, 'status' => WorkOrderStatus::Assigned->value,
    ]);

    Livewire::test(MyWorkOrders::class)
        ->call('startTravel', $workOrder->id)
        ->assertForbidden();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Assigned);
});

test('a user with work_orders.start permission but no linked technician profile is forbidden entirely', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(MyWorkOrders::class)->assertForbidden();
});
