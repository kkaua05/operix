<?php

use App\Enums\WorkOrderStatus;
use App\Livewire\Dashboard\Index;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use App\Support\CurrentCompany;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('a new company with no data sees the onboarding checklist', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Index::class)
        ->assertSee('Primeiros passos')
        ->assertSee('Cadastre seu primeiro cliente')
        ->assertSee('Cadastre sua equipe técnica');
});

test('the onboarding checklist disappears once customers and technicians exist', function () {
    $user = actingAsCompanyUser(['admin']);
    Customer::factory()->create(['company_id' => $user->company_id]);
    Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)->assertDontSee('Primeiros passos');
});

test('it shows work orders whose sla is at risk, sorted by the closest due date', function () {
    $user = actingAsCompanyUser(['admin']);

    $critical = createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::InProgress->value,
        'sla_status' => 'critical', 'sla_due_at' => now()->addHour(),
    ]);
    $ok = createWorkOrderForCompany($user->company_id, [
        'status' => WorkOrderStatus::InProgress->value,
        'sla_status' => 'normal', 'sla_due_at' => now()->addDays(3),
    ]);

    Livewire::test(Index::class)
        ->assertSee($critical->number)
        ->assertDontSee($ok->number);
});

test('a super admin with no company sees a message instead of a company dashboard', function () {
    CurrentCompany::clear();

    $user = User::factory()->create(['company_id' => null, 'is_super_admin' => true]);
    $this->actingAs($user);

    Livewire::test(Index::class)->assertSee('Sem dados para exibir');
});
