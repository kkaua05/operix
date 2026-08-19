<?php

use App\Livewire\Technicians\Index;
use App\Models\Technician;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('technicians.index'))->assertRedirect('/login');
});

test('a user with no roles at all is forbidden', function () {
    actingAsCompanyUser([]);

    $this->get(route('technicians.index'))->assertForbidden();
});

test('a user with a role granting technicians.view can access the list', function () {
    actingAsCompanyUser(['dispatcher']);

    $this->get(route('technicians.index'))->assertOk();
});

test('it lists only the current company\'s technicians', function () {
    $user = actingAsCompanyUser(['admin']);

    Technician::factory()->count(3)->create(['company_id' => $user->company_id]);
    $otherCompanyTechnician = Technician::factory()->create();

    Livewire::test(Index::class)
        ->assertSee(Technician::where('company_id', $user->company_id)->first()->name)
        ->assertDontSee($otherCompanyTechnician->name);
});

test('it shows an empty state when there are no technicians', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Index::class)->assertSee('Nenhum técnico encontrado');
});

test('it searches technicians by registration number', function () {
    $user = actingAsCompanyUser(['admin']);

    $match = Technician::factory()->create(['company_id' => $user->company_id, 'registration_number' => 'TEC-99999']);
    $other = Technician::factory()->create(['company_id' => $user->company_id, 'registration_number' => 'TEC-11111']);

    Livewire::test(Index::class)
        ->set('search', 'TEC-99999')
        ->assertSee($match->name)
        ->assertDontSee($other->name);
});

test('a user with technicians.manage permission can delete a technician', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $technician->id)
        ->call('delete');

    expect(Technician::find($technician->id))->toBeNull();
});

test('a user without technicians.manage permission cannot delete a technician', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $technician->id)
        ->call('delete')
        ->assertForbidden();

    expect(Technician::find($technician->id))->not->toBeNull();
});
