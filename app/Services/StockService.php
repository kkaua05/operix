<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for every stock-quantity change (§32-34): every
 * movement is recorded in inventory_movements and atomically reflected on
 * products.stock_quantity inside a locked transaction, so concurrent
 * requests can never race past each other and desync the running balance.
 */
class StockService
{
    /**
     * Records a manual movement (entrada/saída/ajuste) not tied to a work
     * order. "adjustment" sets the absolute new quantity; every other type
     * adds (in/return) or subtracts (out/consumption) the given quantity.
     */
    public function registerMovement(
        Product $product,
        InventoryMovementType $type,
        float $quantity,
        ?User $user = null,
        ?string $notes = null,
        ?Model $reference = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $user, $notes, $reference) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $delta = match ($type) {
                InventoryMovementType::In, InventoryMovementType::Return => $quantity,
                InventoryMovementType::Out, InventoryMovementType::Consumption => -$quantity,
                InventoryMovementType::Adjustment => $quantity - (float) $locked->stock_quantity,
            };

            $newQuantity = (float) $locked->stock_quantity + $delta;

            if ($newQuantity < 0) {
                throw InsufficientStockException::make($locked, $quantity);
            }

            $locked->update(['stock_quantity' => $newQuantity]);

            $movement = $locked->movements()->create([
                'company_id' => $locked->company_id,
                'type' => $type,
                'quantity' => $type === InventoryMovementType::Adjustment ? $delta : $quantity,
                'unit_cost' => $locked->cost_price,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'performed_by' => $user?->id,
                'notes' => $notes,
            ]);

            $product->setAttribute('stock_quantity', $newQuantity);

            return $movement;
        });
    }

    /**
     * Attaches a product as a consumed material on a work order (§34) and
     * deducts it from stock in the same transaction — a failed deduction
     * (insufficient stock) rolls back the material record too.
     */
    public function consumeForWorkOrder(WorkOrder $workOrder, Product $product, float $quantity, ?User $user = null): WorkOrderMaterial
    {
        return DB::transaction(function () use ($workOrder, $product, $quantity, $user) {
            $material = $workOrder->materials()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $product->cost_price,
                'total_cost' => $quantity * (float) $product->cost_price,
                'registered_by' => $user?->id,
            ]);

            $this->registerMovement(
                product: $product,
                type: InventoryMovementType::Consumption,
                quantity: $quantity,
                user: $user,
                notes: "Consumo na OS {$workOrder->number}",
                reference: $material,
            );

            return $material;
        });
    }

    /**
     * Reverses a previously consumed material: returns the quantity to
     * stock and removes the material line from the work order.
     */
    public function returnFromWorkOrder(WorkOrderMaterial $material, ?User $user = null): void
    {
        DB::transaction(function () use ($material, $user) {
            $this->registerMovement(
                product: $material->product,
                type: InventoryMovementType::Return,
                quantity: (float) $material->quantity,
                user: $user,
                notes: "Devolução da OS {$material->workOrder->number}",
                reference: $material,
            );

            $material->delete();
        });
    }
}
