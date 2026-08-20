<?php

use App\Livewire\WorkOrders\Index;
use App\Models\Customer;
use App\Models\WorkOrder;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('work-orders.index'))->assertRedirect('/login');
});

test('a user with no roles at all is forbidden', function () {
    actingAsCompanyUser([]);

    $this->get(route('work-orders.index'))->assertForbidden();
});

test('a user with a role granting work_orders.view can access the list', function () {
    actingAsCompanyUser(['technician']);

    $this->get(route('work-orders.index'))->assertOk();
});

test('it lists only the current company\'s work orders', function () {
    $user = actingAsCompanyUser(['admin']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $ownOrder = WorkOrder::create([
        'company_id' => $user->company_id,
        'number' => 'OS-00001',
        'customer_id' => $customer->id,
    ]);

    $otherOrder = WorkOrder::factory()->create();

    Livewire::test(Index::class)
        ->assertSee($ownOrder->number)
        ->assertDontSee($otherOrder->number);
});

test('it shows an empty state when there are no work orders', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Index::class)->assertSee('Nenhuma ordem de serviço encontrada');
});

test('it filters by status and priority', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    $urgent = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'priority' => 'urgent', 'status' => 'new',
    ]);
    $low = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00002', 'customer_id' => $customer->id,
        'priority' => 'low', 'status' => 'new',
    ]);

    Livewire::test(Index::class)
        ->set('priority', 'urgent')
        ->assertSee($urgent->number)
        ->assertDontSee($low->number);
});

test('a user with work_orders.delete permission can delete a work order', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $workOrder->id)
        ->call('delete');

    expect(WorkOrder::find($workOrder->id))->toBeNull();
});
