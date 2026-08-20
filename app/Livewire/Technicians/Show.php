<?php

namespace App\Livewire\Technicians;

use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Technician $technician;

    #[Url(as: 'aba')]
    public string $activeTab = 'perfil';

    public function mount(Technician $technician): void
    {
        $this->authorize('view', $technician);

        $this->technician = $technician;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(): View
    {
        $this->technician->loadCount(['skills', 'workOrders', 'ratings']);
        $this->technician->load('supervisor');

        return view('livewire.technicians.show', [
            'averageRating' => $this->technician->ratings()->avg('score'),
            'ratings' => $this->activeTab === 'avaliacoes' ? $this->technician->ratings()->with('customer')->latest()->get() : collect(),
            'upcomingAppointments' => $this->activeTab === 'agenda'
                ? $this->technician->appointments()->with('workOrder.customer')->where('scheduled_start', '>=', now())->orderBy('scheduled_start')->get()
                : collect(),
        ]);
    }
}
