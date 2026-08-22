<?php

use App\Livewire\Inventory\Categories\Manager;
use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Livewire;

test('a user without inventory.view is forbidden from the category manager', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Manager::class)->assertForbidden();
});

test('a user with inventory.manage can create a category and a nested subcategory', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Manager::class)
        ->call('addNew')
        ->set('name', 'Redes')
        ->call('save')
        ->assertHasNoErrors();

    $parent = ProductCategory::where('name', 'Redes')->firstOrFail();

    Livewire::test(Manager::class)
        ->call('addNew')
        ->set('name', 'Cabos')
        ->set('parent_id', $parent->id)
        ->call('save')
        ->assertHasNoErrors();

    $child = ProductCategory::where('name', 'Cabos')->firstOrFail();
    expect($child->parent_id)->toBe($parent->id);
});

test('category name must be unique within the same company', function () {
    $user = actingAsCompanyUser(['admin']);
    ProductCategory::factory()->create(['company_id' => $user->company_id, 'name' => 'Ferramentas']);

    Livewire::test(Manager::class)
        ->call('addNew')
        ->set('name', 'Ferramentas')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('deleting a category with products nulls their category instead of failing', function () {
    $user = actingAsCompanyUser(['admin']);
    $category = ProductCategory::factory()->create(['company_id' => $user->company_id]);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'product_category_id' => $category->id]);

    Livewire::test(Manager::class)
        ->call('confirmDelete', $category->id)
        ->call('delete');

    expect(ProductCategory::find($category->id))->toBeNull()
        ->and($product->fresh()->product_category_id)->toBeNull();
});
