<?php

use App\Livewire\Teams\Form;
use App\Models\Team;
use App\Models\Technician;
use Livewire\Livewire;

test('a user with teams.manage permission can create a team', function () {
    $user = actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', 'Equipe Instalação')
        ->set('region', 'Zona Sul')
        ->set('capacity', 5)
        ->call('save')
        ->assertHasNoErrors();

    $team = Team::where('name', 'Equipe Instalação')->first();

    expect($team)->not->toBeNull()
        ->and($team->company_id)->toBe($user->company_id)
        ->and($team->capacity)->toBe(5);
});

test('name is required to create a team', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('team name must be unique within the same company', function () {
    $user = actingAsCompanyUser(['admin']);

    Team::factory()->create(['company_id' => $user->company_id, 'name' => 'Equipe Norte']);

    Livewire::test(Form::class)
        ->set('name', 'Equipe Norte')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

test('the same team name can be used by a different company', function () {
    $user = actingAsCompanyUser(['admin']);

    Team::factory()->create(['name' => 'Equipe Norte']);

    Livewire::test(Form::class)
        ->set('name', 'Equipe Norte')
        ->call('save')
        ->assertHasNoErrors();

    expect(Team::where('company_id', $user->company_id)->where('name', 'Equipe Norte')->exists())->toBeTrue();
});

test('supervisor_id must belong to the same company', function () {
    actingAsCompanyUser(['admin']);

    $foreignTechnician = Technician::factory()->create();

    Livewire::test(Form::class)
        ->set('name', 'Equipe Nova')
        ->set('supervisor_id', $foreignTechnician->id)
        ->call('save')
        ->assertHasErrors(['supervisor_id']);
});

test('a user without teams.manage permission cannot access the create form', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Form::class)->assertForbidden();
});

test('a user can edit a team', function () {
    $user = actingAsCompanyUser(['admin']);

    $team = Team::factory()->create(['company_id' => $user->company_id, 'name' => 'Nome Antigo']);

    Livewire::test(Form::class, ['team' => $team])
        ->set('name', 'Nome Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($team->fresh()->name)->toBe('Nome Novo');
});

test('a user cannot edit a team from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignTeam = Team::factory()->create();
    $foreignTeam = Team::withoutCompanyScope()->find($foreignTeam->id);

    Livewire::test(Form::class, ['team' => $foreignTeam])->assertForbidden();
});
