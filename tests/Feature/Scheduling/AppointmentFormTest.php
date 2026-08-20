<?php

use App\Livewire\Scheduling\Form;
use App\Models\Appointment;
use App\Models\Technician;
use Livewire\Livewire;

test('a user with scheduling.manage permission can create an appointment', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Form::class)
        ->set('work_order_id', $workOrder->id)
        ->set('technician_id', $technician->id)
        ->set('scheduled_start', '2026-08-24T09:00')
        ->set('scheduled_end', '2026-08-24T11:00')
        ->call('save')
        ->assertHasNoErrors();

    $appointment = Appointment::where('work_order_id', $workOrder->id)->first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->company_id)->toBe($user->company_id)
        ->and($appointment->technician_id)->toBe($technician->id);
});

test('work_order_id is required', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Form::class)
        ->set('scheduled_start', '2026-08-24T09:00')
        ->set('scheduled_end', '2026-08-24T11:00')
        ->call('save')
        ->assertHasErrors(['work_order_id' => 'required']);
});

test('scheduled_end must be after scheduled_start', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(Form::class)
        ->set('work_order_id', $workOrder->id)
        ->set('scheduled_start', '2026-08-24T11:00')
        ->set('scheduled_end', '2026-08-24T09:00')
        ->call('save')
        ->assertHasErrors(['scheduled_end' => 'after']);
});

test('at least one of technician or team must be selected', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(Form::class)
        ->set('work_order_id', $workOrder->id)
        ->set('scheduled_start', '2026-08-24T09:00')
        ->set('scheduled_end', '2026-08-24T11:00')
        ->call('save')
        ->assertHasErrors(['technician_id']);
});

test('it rejects a conflicting appointment for the same technician', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id, 'technician_id' => $technician->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
    ]);

    $secondWorkOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(Form::class)
        ->set('work_order_id', $secondWorkOrder->id)
        ->set('technician_id', $technician->id)
        ->set('scheduled_start', '2026-08-24T10:00')
        ->set('scheduled_end', '2026-08-24T12:00')
        ->call('save')
        ->assertHasErrors(['scheduled_start']);

    expect(Appointment::where('work_order_id', $secondWorkOrder->id)->exists())->toBeFalse();
});

test('technician_id must belong to the same company', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);
    $foreignTechnician = Technician::factory()->create();

    Livewire::test(Form::class)
        ->set('work_order_id', $workOrder->id)
        ->set('technician_id', $foreignTechnician->id)
        ->set('scheduled_start', '2026-08-24T09:00')
        ->set('scheduled_end', '2026-08-24T11:00')
        ->call('save')
        ->assertHasErrors(['technician_id']);
});

test('a user without scheduling.manage permission cannot access the create form', function () {
    actingAsCompanyUser([]);

    Livewire::test(Form::class)->assertForbidden();
});

test('a user can edit an appointment without triggering a false conflict against itself', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $workOrder = createWorkOrderForCompany($user->company_id);

    $appointment = Appointment::create([
        'company_id' => $user->company_id, 'work_order_id' => $workOrder->id, 'technician_id' => $technician->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
    ]);

    Livewire::test(Form::class, ['appointment' => $appointment])
        ->set('notes', 'Reagendado com o cliente')
        ->call('save')
        ->assertHasNoErrors();

    expect($appointment->fresh()->notes)->toBe('Reagendado com o cliente');
});
