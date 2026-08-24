<?php

use App\Livewire\Audit\Index;
use App\Models\AuditLog;
use App\Models\Company;
use Livewire\Livewire;

test('a user without audit.view is forbidden', function () {
    actingAsCompanyUser(['technician']);

    Livewire::test(Index::class)->assertForbidden();
});

test('it lists only the current company logs and filters by action', function () {
    $admin = actingAsCompanyUser(['admin']);

    AuditLog::create(['company_id' => $admin->company_id, 'user_id' => $admin->id, 'action' => 'user.created']);
    AuditLog::create(['company_id' => $admin->company_id, 'user_id' => $admin->id, 'action' => 'customer.deleted']);
    $otherCompany = Company::factory()->create();
    AuditLog::create(['company_id' => $otherCompany->id, 'action' => 'user.created']);

    Livewire::test(Index::class)
        ->assertViewHas('logs', fn ($logs) => $logs->total() === 2)
        ->set('action', 'customer.deleted')
        ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->action === 'customer.deleted');
});
