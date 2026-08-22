<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function make(Product $product, float $requested): self
    {
        return new self(
            "Estoque insuficiente para \"{$product->name}\": disponível {$product->stock_quantity}, solicitado {$requested}."
        );
    }
}
