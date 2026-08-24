<?php

use App\Livewire\Users\Form;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

test('a user without users.manage cannot create a user', function () {
    actingAsCompanyUser(['technician']);

    Livewire::test(Form::class)->assertForbidden();
});

test('an admin can create a user with a role and it is audited', function () {
    $admin = actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', 'Novo Colega')
        ->set('email', 'novo.colega@example.com')
        ->set('password', 'password123')
        ->set('role', 'dispatcher')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'novo.colega@example.com')->firstOrFail();
    expect($user->company_id)->toBe($admin->company_id)
        ->and($user->hasRole('dispatcher'))->toBeTrue();

    expect(AuditLog::where('action', 'user.created')->where('auditable_id', $user->id)->exists())->toBeTrue();
});

test('email must be unique', function () {
    $admin = actingAsCompanyUser(['admin']);
    $existing = User::factory()->create(['company_id' => $admin->company_id]);

    Livewire::test(Form::class)
        ->set('name', 'Duplicado')
        ->set('email', $existing->email)
        ->set('password', 'password123')
        ->set('role', 'support')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('password is required when creating but optional when editing', function () {
    $admin = actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', 'Sem Senha')
        ->set('email', 'sem.senha@example.com')
        ->set('role', 'support')
        ->call('save')
        ->assertHasErrors(['password']);

    $colleague = User::factory()->create(['company_id' => $admin->company_id]);
    $colleague->assignRole('support');

    Livewire::test(Form::class, ['user' => $colleague])
        ->set('name', 'Nome Atualizado')
        ->set('role', 'support')
        ->call('save')
        ->assertHasNoErrors();

    expect($colleague->fresh()->name)->toBe('Nome Atualizado');
});

test('changing a user role is recorded in the audit log', function () {
    $admin = actingAsCompanyUser(['admin']);
    $colleague = User::factory()->create(['company_id' => $admin->company_id]);
    $colleague->assignRole('support');

    Livewire::test(Form::class, ['user' => $colleague])
        ->set('role', 'manager')
        ->call('save')
        ->assertHasNoErrors();

    expect($colleague->fresh()->hasRole('manager'))->toBeTrue()
        ->and($colleague->fresh()->hasRole('support'))->toBeFalse();

    expect(AuditLog::where('action', 'user.role_changed')->where('auditable_id', $colleague->id)->exists())->toBeTrue();
});

test('a user from another company cannot be edited', function () {
    actingAsCompanyUser(['admin']);
    $otherCompany = Company::factory()->create();
    $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

    Livewire::test(Form::class, ['user' => $otherUser])->assertForbidden();
});
