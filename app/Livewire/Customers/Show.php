<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Customer $customer;

    #[Url(as: 'aba')]
    public string $activeTab = 'resumo';

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);

        $this->customer = $customer;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(): View
    {
        $this->customer->loadCount(['addresses', 'contacts', 'equipment', 'workOrders']);

        return view('livewire.customers.show');
    }
}
