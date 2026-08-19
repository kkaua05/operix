<?php

use App\Livewire\Technicians\Form;
use App\Models\Technician;
use Livewire\Livewire;

test('a user with technicians.manage permission can create a technician', function () {
    $user = actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', 'Carlos Pereira')
        ->set('registration_number', 'TEC-0001')
        ->set('daily_capacity', 6)
        ->call('save')
        ->assertHasNoErrors();

    $technician = Technician::where('name', 'Carlos Pereira')->first();

    expect($technician)->not->toBeNull()
        ->and($technician->company_id)->toBe($user->company_id)
        ->and($technician->daily_capacity)->toBe(6);
});

test('name is required to create a technician', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('registration_number must be unique within the same company', function () {
    $user = actingAsCompanyUser(['admin']);

    Technician::factory()->create(['company_id' => $user->company_id, 'registration_number' => 'TEC-DUP']);

    Livewire::test(Form::class)
        ->set('name', 'Outro Técnico')
        ->set('registration_number', 'TEC-DUP')
        ->call('save')
        ->assertHasErrors(['registration_number' => 'unique']);
});

test('a technician cannot be its own supervisor', function () {
    $user = actingAsCompanyUser(['admin']);

    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Form::class, ['technician' => $technician])
        ->set('supervisor_id', $technician->id)
        ->call('save')
        ->assertHasErrors(['supervisor_id']);
});

test('supervisor_id must belong to the same company', function () {
    $user = actingAsCompanyUser(['admin']);

    $foreignSupervisor = Technician::factory()->create();

    Livewire::test(Form::class)
        ->set('name', 'Novo Técnico')
        ->set('supervisor_id', $foreignSupervisor->id)
        ->call('save')
        ->assertHasErrors(['supervisor_id']);
});

test('a user without technicians.manage permission cannot access the create form', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Form::class)->assertForbidden();
});

test('a user can edit a technician', function () {
    $user = actingAsCompanyUser(['admin']);

    $technician = Technician::factory()->create(['company_id' => $user->company_id, 'name' => 'Nome Antigo']);

    Livewire::test(Form::class, ['technician' => $technician])
        ->set('name', 'Nome Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($technician->fresh()->name)->toBe('Nome Novo');
});

test('a user cannot edit a technician from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignTechnician = Technician::factory()->create();
    $foreignTechnician = Technician::withoutCompanyScope()->find($foreignTechnician->id);

    Livewire::test(Form::class, ['technician' => $foreignTechnician])->assertForbidden();
});
