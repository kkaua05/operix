<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'type' => fake()->randomElement(['Roteador', 'Modem', 'Central de alarme', 'Ar-condicionado', 'Nobreak']),
            'manufacturer' => fake()->randomElement(['TP-Link', 'Intelbras', 'Huawei', 'Cisco', 'LG']),
            'model' => fake()->bothify('MDL-####'),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'asset_tag' => fake()->optional()->bothify('PAT-#####'),
            'installed_at' => fake()->dateTimeBetween('-3 years', 'now'),
            'warranty_expires_at' => fake()->dateTimeBetween('now', '+2 years'),
            'status' => 'active',
        ];
    }
}
