<?php

use App\Livewire\Teams\Show;
use App\Models\Team;
use Livewire\Livewire;

test('a user can view a team from their own company', function () {
    $user = actingAsCompanyUser(['admin']);

    $team = Team::factory()->create(['company_id' => $user->company_id, 'name' => 'Equipe Teste']);

    Livewire::test(Show::class, ['team' => $team])
        ->assertSee('Equipe Teste')
        ->assertOk();
});

test('a user cannot view a team from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignTeam = Team::factory()->create();
    $foreignTeam = Team::withoutCompanyScope()->find($foreignTeam->id);

    Livewire::test(Show::class, ['team' => $foreignTeam])->assertForbidden();
});

test('the show route 404s for a team from another company via route model binding', function () {
    actingAsCompanyUser(['admin']);

    $foreignTeam = Team::factory()->create();

    $this->get(route('teams.show', $foreignTeam))->assertNotFound();
});

test('tabs switch between team sections', function () {
    $user = actingAsCompanyUser(['admin']);

    $team = Team::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Show::class, ['team' => $team])
        ->assertSet('activeTab', 'visao-geral')
        ->call('setTab', 'tecnicos')
        ->assertSet('activeTab', 'tecnicos');
});
