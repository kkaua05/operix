<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }
}
