<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => 'individual',
            'name' => fake()->name(),
            'document' => fake()->unique()->numerify('###.###.###-##'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(##) ####-####'),
            'mobile_phone' => fake()->numerify('(##) #####-####'),
            'status' => 'active',
        ];
    }
}
