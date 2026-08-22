<?php

use App\Enums\WorkOrderStatus;
use App\Livewire\Dispatch\Center;
use App\Models\Appointment;
use App\Models\Technician;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('dispatch.index'))->assertRedirect('/login');
});

test('a user without dispatch.view permission is forbidden', function () {
    actingAsCompanyUser(['technician']);

    $this->get(route('dispatch.index'))->assertForbidden();
});

test('a user with dispatch.view permission can access the center', function () {
    actingAsCompanyUser(['dispatcher']);

    $this->get(route('dispatch.index'))->assertOk();
});

test('it lists only unassigned, open work orders in the pending column', function () {
    $user = actingAsCompanyUser(['dispatcher']);

    $pending = createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value]);
    $alreadyAssigned = createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::Assigned->value,
        'technician_id' => Technician::factory()->create(['company_id' => $user->company_id])->id,
    ]);
    $completed = createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::Completed->value]);

    Livewire::test(Center::class)
        ->assertSee($pending->number)
        ->assertDontSee($alreadyAssigned->number)
        ->assertDontSee($completed->number);
});

test('pending work orders are sorted by priority, most urgent first', function () {
    $user = actingAsCompanyUser(['dispatcher']);

    $low = createWorkOrderForCompany($user->company_id, ['priority' => 'low']);
    $critical = createWorkOrderForCompany($user->company_id, ['priority' => 'critical']);

    $html = Livewire::test(Center::class)->html();

    expect(strpos($html, $critical->number))->toBeLessThan(strpos($html, $low->number));
});

test('a dispatcher can assign a technician to a pending work order via drag and drop', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::Scheduled->value]);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Center::class)
        ->call('assign', $workOrder->id, $technician->id)
        ->assertHasNoErrors();

    expect($workOrder->fresh()->technician_id)->toBe($technician->id)
        ->and($workOrder->fresh()->status)->toBe(WorkOrderStatus::Assigned);
});

test('assigning a technician from another company is forbidden', function () {
    // The BelongsToCompany global scope makes the foreign technician
    // unresolvable to begin with — findOrFail throws before the explicit
    // same-company guard in assign() would even run (same shape of case
    // as Fase 8's team member manager).
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);
    $foreignTechnician = Technician::factory()->create();

    expect(fn () => Livewire::test(Center::class)->call('assign', $workOrder->id, $foreignTechnician->id))
        ->toThrow(ModelNotFoundException::class);

    expect($workOrder->fresh()->technician_id)->toBeNull();
});

test('a user without work_orders.assign permission cannot assign', function () {
    // No default role has dispatch.view without also having
    // work_orders.assign, so grant dispatch.view directly to isolate the
    // ability being tested rather than failing earlier in mount().
    $user = actingAsCompanyUser([]);
    $user->givePermissionTo('dispatch.view');
    $workOrder = createWorkOrderForCompany($user->company_id);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Center::class)
        ->call('assign', $workOrder->id, $technician->id)
        ->assertForbidden();
});

test('a dispatcher can change the priority of a pending work order', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id, ['priority' => 'low']);

    Livewire::test(Center::class)
        ->call('changePriority', $workOrder->id, 'critical');

    expect($workOrder->fresh()->priority->value)->toBe('critical');
});

test('it shows today\'s appointments in the middle column', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);
    Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id,
        'scheduled_start' => now()->setTime(9, 0), 'scheduled_end' => now()->setTime(10, 0),
    ]);

    Livewire::test(Center::class)->assertSee($workOrder->number);
});
