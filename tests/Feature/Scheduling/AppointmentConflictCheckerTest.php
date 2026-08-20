<?php

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\AppointmentConflictChecker;
use Illuminate\Support\Carbon;

test('it detects an overlapping appointment for the same technician', function () {
    $company = Company::factory()->create();
    $technician = Technician::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::factory()->create(['company_id' => $company->id]);

    Appointment::create([
        'company_id' => $company->id, 'work_order_id' => $workOrder->id, 'technician_id' => $technician->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
    ]);

    $hasConflict = (new AppointmentConflictChecker)->hasConflict(
        $company->id, $technician->id, null,
        Carbon::parse('2026-08-24 10:00:00'), Carbon::parse('2026-08-24 12:00:00'),
    );

    expect($hasConflict)->toBeTrue();
});

test('it does not flag back-to-back appointments that only touch at the boundary', function () {
    $company = Company::factory()->create();
    $technician = Technician::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::factory()->create(['company_id' => $company->id]);

    Appointment::create([
        'company_id' => $company->id, 'work_order_id' => $workOrder->id, 'technician_id' => $technician->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
    ]);

    $hasConflict = (new AppointmentConflictChecker)->hasConflict(
        $company->id, $technician->id, null,
        Carbon::parse('2026-08-24 11:00:00'), Carbon::parse('2026-08-24 12:00:00'),
    );

    expect($hasConflict)->toBeFalse();
});

test('it ignores cancelled and no-show appointments', function () {
    $company = Company::factory()->create();
    $technician = Technician::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::factory()->create(['company_id' => $company->id]);

    Appointment::create([
        'company_id' => $company->id, 'work_order_id' => $workOrder->id, 'technician_id' => $technician->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
        'status' => 'cancelled',
    ]);

    $hasConflict = (new AppointmentConflictChecker)->hasConflict(
        $company->id, $technician->id, null,
        Carbon::parse('2026-08-24 09:30:00'), Carbon::parse('2026-08-24 10:30:00'),
    );

    expect($hasConflict)->toBeFalse();
});

test('it does not flag a different technician for the same window', function () {
    $company = Company::factory()->create();
    $technicianA = Technician::factory()->create(['company_id' => $company->id]);
    $technicianB = Technician::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::factory()->create(['company_id' => $company->id]);

    Appointment::create([
        'company_id' => $company->id, 'work_order_id' => $workOrder->id, 'technician_id' => $technicianA->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
    ]);

    $hasConflict = (new AppointmentConflictChecker)->hasConflict(
        $company->id, $technicianB->id, null,
        Carbon::parse('2026-08-24 09:30:00'), Carbon::parse('2026-08-24 10:30:00'),
    );

    expect($hasConflict)->toBeFalse();
});

test('it excludes the appointment being edited from the conflict check', function () {
    $company = Company::factory()->create();
    $technician = Technician::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::factory()->create(['company_id' => $company->id]);

    $appointment = Appointment::create([
        'company_id' => $company->id, 'work_order_id' => $workOrder->id, 'technician_id' => $technician->id,
        'scheduled_start' => '2026-08-24 09:00:00', 'scheduled_end' => '2026-08-24 11:00:00',
    ]);

    $hasConflict = (new AppointmentConflictChecker)->hasConflict(
        $company->id, $technician->id, null,
        Carbon::parse('2026-08-24 09:00:00'), Carbon::parse('2026-08-24 11:00:00'),
        $appointment->id,
    );

    expect($hasConflict)->toBeFalse();
});

test('it returns no conflicts when neither technician nor team is given', function () {
    $company = Company::factory()->create();

    $conflicts = (new AppointmentConflictChecker)->conflictingAppointments(
        $company->id, null, null,
        Carbon::parse('2026-08-24 09:00:00'), Carbon::parse('2026-08-24 11:00:00'),
    );

    expect($conflicts)->toBeEmpty();
});
