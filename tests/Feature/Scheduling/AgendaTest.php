<?php

use App\Livewire\Scheduling\Agenda;
use App\Models\Appointment;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function () {
    Carbon::setTestNow();
});

test('guests are redirected to login', function () {
    $this->get(route('scheduling.index'))->assertRedirect('/login');
});

test('a user with no roles at all is forbidden', function () {
    actingAsCompanyUser([]);

    $this->get(route('scheduling.index'))->assertForbidden();
});

test('a user with scheduling.view can access the agenda', function () {
    actingAsCompanyUser(['dispatcher']);

    $this->get(route('scheduling.index'))->assertOk();
});

test('the day view shows only appointments for the selected day', function () {
    $user = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    $today = Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id,
        'scheduled_start' => now()->setTime(9, 0), 'scheduled_end' => now()->setTime(10, 0),
    ]);
    $tomorrow = Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id,
        'scheduled_start' => now()->addDay()->setTime(9, 0), 'scheduled_end' => now()->addDay()->setTime(10, 0),
    ]);

    Livewire::test(Agenda::class)
        ->assertSee($workOrder->number) // today's appointment shows
        ->assertSet('date', now()->toDateString());
});

test('it shows an empty state when there are no appointments for the day', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Agenda::class)->assertSee('Nenhum agendamento neste dia');
});

test('navigating next and previous moves the date by one day in day view', function () {
    Carbon::setTestNow('2026-08-24 10:00:00');
    actingAsCompanyUser(['admin']);

    Livewire::test(Agenda::class)
        ->assertSet('date', '2026-08-24')
        ->call('next')
        ->assertSet('date', '2026-08-25')
        ->call('previous')
        ->assertSet('date', '2026-08-24');
});

test('navigating next in week view moves by 7 days', function () {
    Carbon::setTestNow('2026-08-24 10:00:00');
    actingAsCompanyUser(['admin']);

    Livewire::test(Agenda::class)
        ->set('view', 'week')
        ->call('next')
        ->assertSet('date', '2026-08-31');
});

test('goToDay switches to day view for the given date', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Agenda::class)
        ->set('view', 'month')
        ->call('goToDay', '2026-08-15')
        ->assertSet('view', 'day')
        ->assertSet('date', '2026-08-15');
});

test('it only shows the current company\'s appointments', function () {
    $user = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id,
        'scheduled_start' => now()->setTime(9, 0), 'scheduled_end' => now()->setTime(10, 0),
    ]);

    $foreignWorkOrder = WorkOrder::factory()->create();
    $foreignAppointment = Appointment::withoutCompanyScope()->create([
        'company_id' => $foreignWorkOrder->company_id, 'work_order_id' => $foreignWorkOrder->id,
        'scheduled_start' => now()->setTime(14, 0), 'scheduled_end' => now()->setTime(15, 0),
    ]);

    Livewire::test(Agenda::class)
        ->assertSee($workOrder->number)
        ->assertDontSee($foreignWorkOrder->number);
});

test('a user with scheduling.manage permission can delete an appointment', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);
    $appointment = Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id,
        'scheduled_start' => now()->setTime(9, 0), 'scheduled_end' => now()->setTime(10, 0),
    ]);

    Livewire::test(Agenda::class)
        ->call('confirmDelete', $appointment->id)
        ->call('delete');

    expect(Appointment::find($appointment->id))->toBeNull();
});
