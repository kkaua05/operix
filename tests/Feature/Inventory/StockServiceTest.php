<?php

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\StockService;

test('registering an "in" movement increases stock and creates a movement record', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 10]);

    $movement = app(StockService::class)->registerMovement($product, InventoryMovementType::In, 5, $user, 'Compra');

    expect($product->fresh()->stock_quantity)->toEqual('15.00')
        ->and($movement->type)->toBe(InventoryMovementType::In)
        ->and($movement->quantity)->toEqual('5.00')
        ->and($movement->performed_by)->toBe($user->id)
        ->and($movement->notes)->toBe('Compra');
});

test('registering an "out" movement decreases stock', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 10]);

    app(StockService::class)->registerMovement($product, InventoryMovementType::Out, 4, $user);

    expect($product->fresh()->stock_quantity)->toEqual('6.00');
});

test('an "out" movement larger than available stock throws InsufficientStockException', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 3]);

    app(StockService::class)->registerMovement($product, InventoryMovementType::Out, 10, $user);
})->throws(InsufficientStockException::class);

test('an "adjustment" movement sets the absolute stock value', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 10]);

    $movement = app(StockService::class)->registerMovement($product, InventoryMovementType::Adjustment, 25, $user);

    expect($product->fresh()->stock_quantity)->toEqual('25.00')
        ->and($movement->quantity)->toEqual('15.00');
});

test('a failed movement does not leave a partial database change', function () {
    $user = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 3]);

    try {
        app(StockService::class)->registerMovement($product, InventoryMovementType::Out, 10, $user);
    } catch (InsufficientStockException) {
        // expected
    }

    expect($product->fresh()->stock_quantity)->toEqual('3.00')
        ->and($product->fresh()->movements()->count())->toBe(0);
});

test('consuming a product for a work order creates a material and deducts stock', function () {
    $technician = actingAsTechnicianUser();
    $product = Product::factory()->create(['company_id' => $technician->company_id, 'stock_quantity' => 20, 'cost_price' => 10]);
    $workOrder = createWorkOrderForCompany($technician->company_id);

    $material = app(StockService::class)->consumeForWorkOrder($workOrder, $product, 3, $technician->user);

    expect($product->fresh()->stock_quantity)->toEqual('17.00')
        ->and($material->quantity)->toEqual('3.00')
        ->and($material->unit_cost)->toEqual('10.00')
        ->and($material->total_cost)->toEqual('30.00')
        ->and($workOrder->materials()->count())->toBe(1);

    $movement = $product->fresh()->movements()->first();
    expect($movement->type)->toBe(InventoryMovementType::Consumption)
        ->and($movement->reference_id)->toBe($material->id);
});

test('consuming more than available stock throws and does not create a material', function () {
    $technician = actingAsTechnicianUser();
    $product = Product::factory()->create(['company_id' => $technician->company_id, 'stock_quantity' => 2]);
    $workOrder = createWorkOrderForCompany($technician->company_id);

    try {
        app(StockService::class)->consumeForWorkOrder($workOrder, $product, 5, $technician->user);
    } catch (InsufficientStockException) {
        // expected
    }

    expect($workOrder->materials()->count())->toBe(0)
        ->and($product->fresh()->stock_quantity)->toEqual('2.00');
});

test('returning a material from a work order restores stock and removes the material', function () {
    $technician = actingAsTechnicianUser();
    $product = Product::factory()->create(['company_id' => $technician->company_id, 'stock_quantity' => 20]);
    $workOrder = createWorkOrderForCompany($technician->company_id);

    $material = app(StockService::class)->consumeForWorkOrder($workOrder, $product, 5, $technician->user);
    expect($product->fresh()->stock_quantity)->toEqual('15.00');

    app(StockService::class)->returnFromWorkOrder($material, $technician->user);

    expect($product->fresh()->stock_quantity)->toEqual('20.00')
        ->and($workOrder->materials()->count())->toBe(0);
});
