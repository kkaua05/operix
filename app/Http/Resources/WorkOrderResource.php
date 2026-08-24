<?php

namespace App\Http\Resources;

use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkOrder */
class WorkOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'category' => $this->category,
            'description' => $this->description,
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->whenLoaded('customer', fn () => $this->customer->name),
            ],
            'technician' => $this->when($this->technician_id !== null, fn () => [
                'id' => $this->technician_id,
                'name' => $this->whenLoaded('technician', fn () => $this->technician->name),
            ]),
            'sla_due_at' => $this->sla_due_at?->toIso8601String(),
            'sla_status' => $this->sla_status->value,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
