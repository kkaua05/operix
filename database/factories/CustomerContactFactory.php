<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerContact>
 */
class CustomerContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'role' => fake()->randomElement(['Financeiro', 'Responsável técnico', 'Compras', null]),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
