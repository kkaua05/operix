<?php

namespace Database\Factories;

use App\Enums\WorkOrderPriority;
use App\Models\Company;
use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    public function definition(): array
    {
        $priority = fake()->randomElement(WorkOrderPriority::cases());

        [$response, $resolution] = match ($priority) {
            WorkOrderPriority::Critical => [15, 120],
            WorkOrderPriority::Urgent => [30, 240],
            WorkOrderPriority::High => [60, 480],
            WorkOrderPriority::Medium => [120, 1440],
            WorkOrderPriority::Low => [240, 4320],
        };

        return [
            'company_id' => Company::factory(),
            'name' => 'SLA '.$priority->label(),
            'priority' => $priority->value,
            'response_time_minutes' => $response,
            'resolution_time_minutes' => $resolution,
            'business_hours_only' => true,
        ];
    }
}
