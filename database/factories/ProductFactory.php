<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 5, 200);

        return [
            'company_id' => Company::factory(),
            'product_category_id' => null,
            'supplier_id' => null,
            'name' => fake()->unique()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'unit' => 'un',
            'stock_quantity' => fake()->numberBetween(10, 100),
            'min_stock' => fake()->numberBetween(5, 15),
            'max_stock' => null,
            'cost_price' => $cost,
            'sale_price' => $cost * 1.4,
            'status' => 'active',
        ];
    }
}
