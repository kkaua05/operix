<?php

namespace Database\Factories;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'number' => 'OS-'.Str::padLeft((string) fake()->unique()->numberBetween(1, 99999), 5, '0'),
            'customer_id' => Customer::factory(),
            'category' => fake()->randomElement(['Instalação', 'Manutenção', 'Reparo', 'Vistoria']),
            'description' => fake()->sentence(),
            'priority' => fake()->randomElement(WorkOrderPriority::cases())->value,
            'status' => WorkOrderStatus::New->value,
            'origin' => 'manual',
        ];
    }
}
