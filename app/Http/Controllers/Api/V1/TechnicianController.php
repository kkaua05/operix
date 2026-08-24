<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianResource;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TechnicianController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Technician::class);

        $technicians = Technician::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return TechnicianResource::collection($technicians);
    }

    public function show(Technician $technician): TechnicianResource
    {
        $this->authorize('view', $technician);

        return new TechnicianResource($technician);
    }
}
