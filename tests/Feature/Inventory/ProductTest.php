<?php

use App\Livewire\Inventory\Products\Form;
use App\Livewire\Inventory\Products\Index;
use App\Livewire\Inventory\Products\Show;
use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Livewire;

test('a user without inventory.view is forbidden from the product list', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Index::class)->assertForbidden();
});

test('a user with inventory.view can list products', function () {
    $user = actingAsCompanyUser(['manager']);
    Product::factory()->create(['company_id' => $user->company_id, 'name' => 'Roteador X100']);

    Livewire::test(Index::class)->assertSee('Roteador X100');
});

test('it filters products by search and by critical stock', function () {
    $user = actingAsCompanyUser(['manager']);
    $ok = Product::factory()->create(['company_id' => $user->company_id, 'name' => 'Cabo de rede', 'stock_quantity' => 50, 'min_stock' => 5]);
    $critical = Product::factory()->create(['company_id' => $user->company_id, 'name' => 'Conector RJ45', 'stock_quantity' => 2, 'min_stock' => 10]);

    Livewire::test(Index::class)
        ->set('search', 'Conector')
        ->assertSee('Conector RJ45')
        ->assertDontSee('Cabo de rede');

    Livewire::test(Index::class)
        ->set('onlyCritical', true)
        ->assertSee('Conector RJ45')
        ->assertDontSee('Cabo de rede');
});

test('a user without inventory.manage cannot create a product', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(Form::class)->assertForbidden();
});

test('a user with inventory.manage can create a product with an initial stock', function () {
    $user = actingAsCompanyUser(['admin']);
    $category = ProductCategory::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Form::class)
        ->set('name', 'Switch 24 portas')
        ->set('sku', 'SW-24P')
        ->set('product_category_id', $category->id)
        ->set('unit', 'un')
        ->set('stock_quantity', 15)
        ->set('min_stock', 3)
        ->set('cost_price', 200)
        ->set('sale_price', 320)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('sku', 'SW-24P')->firstOrFail();
    expect($product->name)->toBe('Switch 24 portas')
        ->and($product->stock_quantity)->toEqual('15.00');
});

test('sku must be unique within the same company', function () {
    $user = actingAsCompanyUser(['admin']);
    Product::factory()->create(['company_id' => $user->company_id, 'sku' => 'DUP-1']);

    Livewire::test(Form::class)
        ->set('name', 'Outro produto')
        ->set('sku', 'DUP-1')
        ->set('unit', 'un')
        ->set('min_stock', 1)
        ->set('cost_price', 1)
        ->set('sale_price', 2)
        ->call('save')
        ->assertHasErrors(['sku']);
});

test('editing a product cannot change stock_quantity directly', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 40]);

    Livewire::test(Form::class, ['product' => $product])
        ->set('name', 'Nome atualizado')
        ->set('stock_quantity', 999)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->name)->toBe('Nome atualizado')
        ->and($product->fresh()->stock_quantity)->toEqual('40.00');
});

test('a user cannot view a product from another company', function () {
    actingAsCompanyUser(['admin']);
    $otherProduct = Product::factory()->create();

    Livewire::test(Show::class, ['product' => $otherProduct])->assertForbidden();
});

test('registering a stock movement on the show page updates the balance', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 10]);

    Livewire::test(Show::class, ['product' => $product])
        ->set('movement_type', 'in')
        ->set('quantity', 5)
        ->call('registerMovement')
        ->assertHasNoErrors();

    expect($product->fresh()->stock_quantity)->toEqual('15.00');
});

test('registering a movement that would go negative shows a friendly error', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 2]);

    Livewire::test(Show::class, ['product' => $product])
        ->set('movement_type', 'out')
        ->set('quantity', 10)
        ->call('registerMovement')
        ->assertHasErrors(['quantity']);

    expect($product->fresh()->stock_quantity)->toEqual('2.00');
});
