<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'document' => fake()->unique()->numerify('##.###.###/####-##'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
            'status' => 'active',
        ];
    }
}
