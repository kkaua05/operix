<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+2 weeks');

        return [
            'company_id' => Company::factory(),
            'work_order_id' => WorkOrder::factory(),
            'scheduled_start' => $start,
            'scheduled_end' => (clone $start)->modify('+2 hours'),
            'status' => AppointmentStatus::Scheduled->value,
        ];
    }
}
