<?php

use App\Livewire\Technicians\Show;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\WorkOrder;
use Livewire\Livewire;

test('a user can view a technician from their own company', function () {
    $user = actingAsCompanyUser(['admin']);

    $technician = Technician::factory()->create(['company_id' => $user->company_id, 'name' => 'Técnico Teste']);

    Livewire::test(Show::class, ['technician' => $technician])
        ->assertSee('Técnico Teste')
        ->assertOk();
});

test('a user cannot view a technician from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignTechnician = Technician::factory()->create();
    $foreignTechnician = Technician::withoutCompanyScope()->find($foreignTechnician->id);

    Livewire::test(Show::class, ['technician' => $foreignTechnician])->assertForbidden();
});

test('the show route 404s for a technician from another company via route model binding', function () {
    actingAsCompanyUser(['admin']);

    $foreignTechnician = Technician::factory()->create();

    $this->get(route('technicians.show', $foreignTechnician))->assertNotFound();
});

test('tabs switch between technician sections', function () {
    $user = actingAsCompanyUser(['admin']);

    $technician = Technician::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Show::class, ['technician' => $technician])
        ->assertSet('activeTab', 'perfil')
        ->call('setTab', 'avaliacoes')
        ->assertSet('activeTab', 'avaliacoes');
});

test('the average rating and rating list reflect real data', function () {
    $user = actingAsCompanyUser(['admin']);

    $technician = Technician::factory()->create(['company_id' => $user->company_id]);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create([
        'company_id' => $user->company_id,
        'number' => 'OS-0001',
        'customer_id' => $customer->id,
        'technician_id' => $technician->id,
    ]);

    $technician->ratings()->create([
        'company_id' => $user->company_id,
        'work_order_id' => $workOrder->id,
        'customer_id' => $customer->id,
        'score' => 5,
        'comment' => 'Excelente atendimento',
    ]);

    Livewire::test(Show::class, ['technician' => $technician])
        ->call('setTab', 'avaliacoes')
        ->assertSee('5.0')
        ->assertSee('Excelente atendimento');
});
