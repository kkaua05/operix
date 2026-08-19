<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\TechnicianSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicianSkill>
 */
class TechnicianSkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'technician_id' => Technician::factory(),
            'skill' => fake()->randomElement([
                'Redes', 'Telecomunicações', 'Climatização', 'Elétrica', 'Segurança eletrônica', 'Informática',
            ]),
            'proficiency_level' => fake()->randomElement(['Básico', 'Intermediário', 'Avançado', null]),
        ];
    }
}
