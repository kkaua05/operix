<?php

namespace App\Livewire\WorkOrders;

use App\Actions\GenerateWorkOrderNumber;
use App\Enums\WorkOrderPriority;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Equipment;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\SlaService;
use App\Services\WorkOrderStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?WorkOrder $workOrder = null;

    public ?int $customer_id = null;

    public ?int $customer_address_id = null;

    public ?int $equipment_id = null;

    public ?int $technician_id = null;

    public ?int $team_id = null;

    public ?int $sla_policy_id = null;

    public string $category = '';

    public string $subcategory = '';

    public string $description = '';

    public string $priority = 'medium';

    public string $origin = 'manual';

    public ?string $scheduled_at = null;

    public ?int $estimated_duration_minutes = null;

    public string $notes = '';

    public function mount(?WorkOrder $workOrder = null): void
    {
        if ($workOrder?->exists) {
            $this->authorize('update', $workOrder);

            $this->workOrder = $workOrder;
            $this->customer_id = $workOrder->customer_id;
            $this->customer_address_id = $workOrder->customer_address_id;
            $this->equipment_id = $workOrder->equipment_id;
            $this->technician_id = $workOrder->technician_id;
            $this->team_id = $workOrder->team_id;
            $this->sla_policy_id = $workOrder->sla_policy_id;
            $this->category = (string) $workOrder->category;
            $this->subcategory = (string) $workOrder->subcategory;
            $this->description = (string) $workOrder->description;
            $this->priority = $workOrder->priority->value;
            $this->origin = $workOrder->origin;
            $this->scheduled_at = $workOrder->scheduled_at?->format('Y-m-d\TH:i');
            $this->estimated_duration_minutes = $workOrder->estimated_duration_minutes;
            $this->notes = (string) $workOrder->notes;
        } else {
            $this->authorize('create', WorkOrder::class);
        }
    }

    public function updatedCustomerId(): void
    {
        $this->customer_address_id = null;
        $this->equipment_id = null;
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'customer_address_id' => ['nullable', Rule::exists('customer_addresses', 'id')->where('customer_id', $this->customer_id)],
            'equipment_id' => ['nullable', Rule::exists('equipment', 'id')->where('customer_id', $this->customer_id)],
            'technician_id' => ['nullable', Rule::exists('technicians', 'id')->where('company_id', $companyId)],
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('company_id', $companyId)],
            'sla_policy_id' => ['nullable', Rule::exists('sla_policies', 'id')->where('company_id', $companyId)],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(array_column(WorkOrderPriority::cases(), 'value'))],
            'origin' => ['required', Rule::in(['manual', 'phone', 'email', 'web', 'api'])],
            'scheduled_at' => ['nullable', 'date'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(WorkOrderStatusService $statusService, SlaService $slaService): void
    {
        $validated = $this->validate();

        if ($this->workOrder) {
            $this->workOrder->update($validated);

            $this->workOrder->sla_due_at = $slaService->calculateDueDate($this->workOrder);
            $this->workOrder->sla_status = $slaService->refreshStatus($this->workOrder);
            $this->workOrder->save();

            session()->flash('status', 'Ordem de serviço atualizada com sucesso.');
        } else {
            $validated['number'] = app(GenerateWorkOrderNumber::class)->handle(auth()->user()->company_id);
            $validated['created_by'] = auth()->id();

            $this->workOrder = WorkOrder::create($validated);
            $statusService->recordCreation($this->workOrder, auth()->user());

            $this->workOrder->sla_due_at = $slaService->calculateDueDate($this->workOrder);
            $this->workOrder->sla_status = $slaService->refreshStatus($this->workOrder);
            $this->workOrder->save();

            session()->flash('status', 'Ordem de serviço criada com sucesso.');
        }

        $this->redirectRoute('work-orders.show', $this->workOrder, navigate: true);
    }

    public function render(): View
    {
        $companyId = auth()->user()->company_id;

        return view('livewire.work-orders.form', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'addresses' => $this->customer_id
                ? CustomerAddress::query()->where('customer_id', $this->customer_id)->get()
                : collect(),
            'equipmentOptions' => $this->customer_id
                ? Equipment::query()->where('customer_id', $this->customer_id)->get()
                : collect(),
            'technicians' => Technician::query()->orderBy('name')->get(['id', 'name']),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
            'slaPolicies' => SlaPolicy::query()->orderBy('name')->get(['id', 'name']),
            'priorities' => WorkOrderPriority::cases(),
        ]);
    }
}
