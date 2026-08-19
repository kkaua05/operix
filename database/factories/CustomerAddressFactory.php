<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['Sede', 'Filial', 'Residência']),
            'type' => 'service',
            'zip_code' => fake()->numerify('#####-###'),
            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => null,
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
