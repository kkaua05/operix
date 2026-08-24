<?php

namespace App\Http\Resources;

use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Technician */
class TechnicianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'registration_number' => $this->registration_number,
            'region' => $this->region,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'daily_capacity' => $this->daily_capacity,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
