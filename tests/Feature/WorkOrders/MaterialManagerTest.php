<?php

use App\Livewire\WorkOrders\MaterialManager;
use App\Models\Product;
use Livewire\Livewire;

test('a user with work_orders.update permission can add a material and stock is deducted', function () {
    $user = actingAsCompanyUser(['manager']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 20, 'cost_price' => 15]);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(MaterialManager::class, ['workOrder' => $workOrder])
        ->set('product_id', $product->id)
        ->set('quantity', 4)
        ->call('save')
        ->assertHasNoErrors();

    expect($workOrder->materials()->count())->toBe(1)
        ->and($product->fresh()->stock_quantity)->toEqual('16.00');
});

test('adding a material with insufficient stock shows an error and does not attach it', function () {
    $user = actingAsCompanyUser(['manager']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 2]);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(MaterialManager::class, ['workOrder' => $workOrder])
        ->set('product_id', $product->id)
        ->set('quantity', 10)
        ->call('save')
        ->assertHasErrors(['quantity']);

    expect($workOrder->materials()->count())->toBe(0)
        ->and($product->fresh()->stock_quantity)->toEqual('2.00');
});

test('a user without work_orders.update permission cannot add a material', function () {
    $user = actingAsCompanyUser(['financial']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 20]);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(MaterialManager::class, ['workOrder' => $workOrder])
        ->set('product_id', $product->id)
        ->set('quantity', 1)
        ->call('save')
        ->assertForbidden();
});

test('deleting a material returns the quantity to stock', function () {
    $user = actingAsCompanyUser(['manager']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 20]);
    $workOrder = createWorkOrderForCompany($user->company_id);

    $component = Livewire::test(MaterialManager::class, ['workOrder' => $workOrder])
        ->set('product_id', $product->id)
        ->set('quantity', 5)
        ->call('save');

    expect($product->fresh()->stock_quantity)->toEqual('15.00');

    $material = $workOrder->materials()->firstOrFail();

    $component->call('delete', $material->id);

    expect($workOrder->materials()->count())->toBe(0)
        ->and($product->fresh()->stock_quantity)->toEqual('20.00');
});
