<?php

use App\Livewire\Technicians\SkillManager;
use App\Models\Technician;
use Livewire\Livewire;

test('a user can add a skill to a technician', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(SkillManager::class, ['technician' => $technician])
        ->call('addNew')
        ->set('skill', 'Redes')
        ->set('proficiency_level', 'Avançado')
        ->call('save')
        ->assertHasNoErrors();

    expect($technician->skills()->count())->toBe(1)
        ->and($technician->skills()->first()->skill)->toBe('Redes');
});

test('skill name is required', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(SkillManager::class, ['technician' => $technician])
        ->call('addNew')
        ->set('skill', '')
        ->call('save')
        ->assertHasErrors(['skill' => 'required']);
});

test('a skill cannot be duplicated for the same technician', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $technician->skills()->create(['skill' => 'Redes']);

    Livewire::test(SkillManager::class, ['technician' => $technician])
        ->call('addNew')
        ->set('skill', 'Redes')
        ->call('save')
        ->assertHasErrors(['skill' => 'unique']);
});

test('a user without technicians.manage permission cannot add a skill', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(SkillManager::class, ['technician' => $technician])
        ->call('addNew')
        ->assertForbidden();
});

test('a user can delete a skill', function () {
    $user = actingAsCompanyUser(['admin']);
    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $skill = $technician->skills()->create(['skill' => 'Elétrica']);

    Livewire::test(SkillManager::class, ['technician' => $technician])
        ->call('delete', $skill->id);

    expect($technician->skills()->count())->toBe(0);
});
