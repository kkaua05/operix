<?php

namespace Database\Factories;

use App\Enums\FinancialTransactionType;
use App\Models\Company;
use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'work_order_id' => null,
            'customer_id' => null,
            'type' => fake()->randomElement(FinancialTransactionType::cases())->value,
            'category' => fake()->randomElement(['Deslocamento', 'Comissão', 'Peças', 'Serviço']),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 10, 500),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        ];
    }
}
