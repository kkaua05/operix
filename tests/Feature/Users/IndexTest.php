<?php

use App\Livewire\Users\Index;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

test('a user without users.manage is forbidden', function () {
    actingAsCompanyUser(['technician']);

    Livewire::test(Index::class)->assertForbidden();
});

test('it lists only the current company users and filters by search', function () {
    $admin = actingAsCompanyUser(['admin']);
    $colleague = User::factory()->create(['company_id' => $admin->company_id, 'name' => 'Fulano da Silva']);
    $otherCompany = Company::factory()->create();
    $other = User::factory()->create(['company_id' => $otherCompany->id, 'name' => 'De Outra Empresa']);

    Livewire::test(Index::class)
        ->assertSee('Fulano da Silva')
        ->assertDontSee('De Outra Empresa')
        ->set('search', 'Fulano')
        ->assertSee('Fulano da Silva');
});

test('an admin can delete another user and it is recorded in the audit log', function () {
    $admin = actingAsCompanyUser(['admin']);
    $colleague = User::factory()->create(['company_id' => $admin->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $colleague->id)
        ->call('delete');

    expect(User::find($colleague->id))->toBeNull();

    $log = AuditLog::where('action', 'user.deleted')->where('auditable_id', $colleague->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id);
});

test('an admin cannot delete their own account', function () {
    $admin = actingAsCompanyUser(['admin']);

    Livewire::test(Index::class)
        ->call('confirmDelete', $admin->id)
        ->call('delete')
        ->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});
