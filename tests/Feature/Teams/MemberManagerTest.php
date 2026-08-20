<?php

use App\Livewire\Teams\MemberManager;
use App\Models\Team;
use App\Models\Technician;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('a user can add a technician to a team', function () {
    $user = actingAsCompanyUser(['admin']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(MemberManager::class, ['team' => $team])
        ->set('addingTechnicianId', (string) $technician->id)
        ->call('addMember');

    expect($team->technicians()->count())->toBe(1)
        ->and($team->technicians()->first()->id)->toBe($technician->id);
});

test('a technician cannot be added twice to the same team', function () {
    $user = actingAsCompanyUser(['admin']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $team->technicians()->attach($technician->id);

    Livewire::test(MemberManager::class, ['team' => $team])
        ->set('addingTechnicianId', (string) $technician->id)
        ->call('addMember');

    expect($team->technicians()->count())->toBe(1);
});

test('a technician from another company cannot be added to the team', function () {
    // The BelongsToCompany global scope makes the foreign technician
    // unresolvable to begin with — findOrFail throws before the explicit
    // same-company guard in addMember() would even run. That guard exists
    // as defense-in-depth for the no-tenant-context case (e.g. a super
    // admin operating without CurrentCompany set), not exercised here.
    $user = actingAsCompanyUser(['admin']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);
    $foreignTechnician = Technician::factory()->create();

    expect(fn () => Livewire::test(MemberManager::class, ['team' => $team])
        ->set('addingTechnicianId', (string) $foreignTechnician->id)
        ->call('addMember')
    )->toThrow(ModelNotFoundException::class);

    expect($team->technicians()->count())->toBe(0);
});

test('a user without teams.manage permission cannot add a member', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(MemberManager::class, ['team' => $team])
        ->set('addingTechnicianId', (string) $technician->id)
        ->call('addMember')
        ->assertForbidden();
});

test('a user can remove a technician from a team', function () {
    $user = actingAsCompanyUser(['admin']);
    $team = Team::factory()->create(['company_id' => $user->company_id]);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $team->technicians()->attach($technician->id);

    Livewire::test(MemberManager::class, ['team' => $team])
        ->call('removeMember', $technician->id);

    expect($team->technicians()->count())->toBe(0);
});
