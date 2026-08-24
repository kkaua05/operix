<?php

namespace App\Livewire\Search;

use App\Models\Customer;
use App\Models\Technician;
use App\Models\WorkOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * The Ctrl+K / Cmd+K global search (§40): a single query fanned out across
 * the entities a user is most likely to be jumping to mid-task — work
 * orders, customers, technicians — each gated behind the same permission
 * its own module page requires, so search never leaks a result the user
 * couldn't otherwise open.
 */
class CommandPalette extends Component
{
    public string $query = '';

    /**
     * @return array{
     *     work_orders: Collection<int, WorkOrder>,
     *     customers: Collection<int, Customer>,
     *     technicians: Collection<int, Technician>,
     * }
     */
    protected function results(): array
    {
        $term = trim($this->query);

        if ($term === '') {
            return ['work_orders' => collect(), 'customers' => collect(), 'technicians' => collect()];
        }

        $user = auth()->user();

        return [
            'work_orders' => $user->can('work_orders.view')
                ? WorkOrder::query()
                    ->where(function ($q) use ($term) {
                        $q->where('number', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    })
                    ->with('customer')
                    ->limit(5)
                    ->get()
                : collect(),
            'customers' => $user->can('customers.view')
                ? Customer::query()
                    ->where(function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                            ->orWhere('document', 'like', "%{$term}%");
                    })
                    ->limit(5)
                    ->get()
                : collect(),
            'technicians' => $user->can('technicians.view')
                ? Technician::query()
                    ->where('name', 'like', "%{$term}%")
                    ->limit(5)
                    ->get()
                : collect(),
        ];
    }

    public function render(): View
    {
        $results = $this->results();

        return view('livewire.search.command-palette', [
            'workOrders' => $results['work_orders'],
            'customers' => $results['customers'],
            'technicians' => $results['technicians'],
            'hasResults' => $results['work_orders']->isNotEmpty()
                || $results['customers']->isNotEmpty()
                || $results['technicians']->isNotEmpty(),
        ]);
    }
}
