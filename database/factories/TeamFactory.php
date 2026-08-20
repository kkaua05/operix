<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Equipe '.fake()->unique()->randomElement(['Norte', 'Sul', 'Leste', 'Oeste', 'Centro', 'Instalação', 'Manutenção']),
            'region' => fake()->randomElement(['Zona Norte', 'Zona Sul', 'Zona Leste', 'Zona Oeste', 'Centro']),
            'capacity' => fake()->numberBetween(2, 8),
            'status' => 'active',
        ];
    }
}
