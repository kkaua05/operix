<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\GenerateWorkOrderNumber;
use App\Http\Controllers\Api\V1\Concerns\ApiResponds;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Services\SlaService;
use App\Services\WorkOrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read/write access to work orders (§48-49) with filtering, sorting, and
 * pagination — the same tenant scoping, authorization, and creation flow
 * as the web UI (WorkOrders\Form, WorkOrderStatusService), just exposed
 * over HTTP for external integrations.
 */
class WorkOrderController extends Controller
{
    use ApiResponds;

    protected const SORTABLE = ['created_at', 'scheduled_at', 'sla_due_at', 'priority', 'status'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkOrder::class);

        $sort = (string) $request->query('sort', '-created_at');
        $sortColumn = ltrim($sort, '-');
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';

        if (! in_array($sortColumn, self::SORTABLE, true)) {
            $sortColumn = 'created_at';
            $sortDirection = 'desc';
        }

        $workOrders = WorkOrder::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('technician_id'), fn ($query) => $query->where('technician_id', $request->query('technician_id')))
            ->with(['customer', 'technician'])
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return WorkOrderResource::collection($workOrders);
    }

    public function show(WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorize('view', $workOrder);

        return new WorkOrderResource($workOrder->load(['customer', 'technician']));
    }

    public function store(
        StoreWorkOrderRequest $request,
        WorkOrderStatusService $statusService,
        SlaService $slaService,
    ): JsonResponse {
        $validated = $request->validated();
        $validated['number'] = app(GenerateWorkOrderNumber::class)->handle($request->user()->company_id);
        $validated['origin'] = 'api';
        $validated['created_by'] = $request->user()->id;

        $workOrder = WorkOrder::create($validated);
        $statusService->recordCreation($workOrder, $request->user());

        $workOrder->sla_due_at = $slaService->calculateDueDate($workOrder);
        $workOrder->sla_status = $slaService->refreshStatus($workOrder);
        $workOrder->save();

        return $this->created(new WorkOrderResource($workOrder->load(['customer', 'technician'])));
    }
}
