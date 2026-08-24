<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\WorkOrderPriority;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkOrder::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'technician_id' => ['nullable', Rule::exists('technicians', 'id')->where('company_id', $companyId)],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(array_column(WorkOrderPriority::cases(), 'value'))],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
