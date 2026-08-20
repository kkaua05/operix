<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Confraternização Universal', 'Tiradentes', 'Independência', 'Natal']),
            'date' => fake()->dateTimeBetween('now', '+1 year'),
            'is_recurring_yearly' => true,
        ];
    }
}
