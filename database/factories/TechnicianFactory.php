<?php

namespace Database\Factories;

use App\Enums\TechnicianStatus;
use App\Models\Company;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technician>
 */
class TechnicianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'document' => fake()->unique()->numerify('###.###.###-##'),
            'phone' => fake()->numerify('(##) #####-####'),
            'email' => fake()->unique()->safeEmail(),
            'registration_number' => fake()->unique()->numerify('TEC-#####'),
            'region' => fake()->randomElement(['Zona Norte', 'Zona Sul', 'Zona Leste', 'Zona Oeste', 'Centro']),
            'status' => TechnicianStatus::Offline->value,
            'daily_capacity' => fake()->numberBetween(4, 10),
        ];
    }
}
