<?php

use App\Livewire\Search\CommandPalette;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\WorkOrder;
use Livewire\Livewire;

test('an empty query returns no results', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(CommandPalette::class)
        ->assertViewHas('hasResults', false);
});

test('it finds a work order by number and a customer by name', function () {
    $user = actingAsCompanyUser(['admin']);

    $workOrder = createWorkOrderForCompany($user->company_id);
    $customer = Customer::factory()->create(['company_id' => $user->company_id, 'name' => 'Cliente Pesquisável']);

    Livewire::test(CommandPalette::class)
        ->set('query', $workOrder->number)
        ->assertSee($workOrder->number);

    Livewire::test(CommandPalette::class)
        ->set('query', 'Pesquisável')
        ->assertSee('Cliente Pesquisável');
});

test('it never returns results from another company', function () {
    $user = actingAsCompanyUser(['admin']);
    $otherWorkOrder = WorkOrder::factory()->create(['number' => 'OS-99999']);

    Livewire::test(CommandPalette::class)
        ->set('query', 'OS-99999')
        ->assertViewHas('hasResults', false);
});

test('a user without technicians.view gets no technician results even for a matching query', function () {
    $user = actingAsCompanyUser(['support']);
    Technician::factory()->create(['company_id' => $user->company_id, 'name' => 'Técnico Oculto']);

    Livewire::test(CommandPalette::class)
        ->set('query', 'Oculto')
        ->assertViewHas('technicians', fn ($technicians) => $technicians->isEmpty());
});
