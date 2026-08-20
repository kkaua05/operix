<?php

use App\Livewire\Teams\Index;
use App\Models\Team;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('teams.index'))->assertRedirect('/login');
});

test('a user with no roles at all is forbidden', function () {
    actingAsCompanyUser([]);

    $this->get(route('teams.index'))->assertForbidden();
});

test('a user with a role granting teams.view can access the list', function () {
    actingAsCompanyUser(['dispatcher']);

    $this->get(route('teams.index'))->assertOk();
});

test('it lists only the current company\'s teams', function () {
    $user = actingAsCompanyUser(['admin']);

    Team::factory()->count(3)->create(['company_id' => $user->company_id]);
    $otherCompanyTeam = Team::factory()->create();

    Livewire::test(Index::class)
        ->assertSee(Team::where('company_id', $user->company_id)->first()->name)
        ->assertDontSee($otherCompanyTeam->name);
});

test('it shows an empty state when there are no teams', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Index::class)->assertSee('Nenhuma equipe encontrada');
});

test('it searches teams by name', function () {
    $user = actingAsCompanyUser(['admin']);

    $match = Team::factory()->create(['company_id' => $user->company_id, 'name' => 'Equipe Alfa']);
    $other = Team::factory()->create(['company_id' => $user->company_id, 'name' => 'Equipe Beta']);

    Livewire::test(Index::class)
        ->set('search', 'Alfa')
        ->assertSee($match->name)
        ->assertDontSee($other->name);
});

test('a user with teams.manage permission can delete a team', function () {
    $user = actingAsCompanyUser(['admin']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $team->id)
        ->call('delete');

    expect(Team::find($team->id))->toBeNull();
});

test('a user without teams.manage permission cannot delete a team', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $team->id)
        ->call('delete')
        ->assertForbidden();

    expect(Team::find($team->id))->not->toBeNull();
});
