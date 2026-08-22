<x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Dashboard
</x-nav-link>

<p class="mt-4 mb-1 px-3 text-[10px] font-semibold tracking-wider text-op-secondary/70 uppercase">
    Operação
</p>
<x-nav-link :href="route('work-orders.index')" :active="request()->routeIs('work-orders.*')">
    Ordens de Serviço
</x-nav-link>
<x-nav-link :href="route('scheduling.index')" :active="request()->routeIs('scheduling.*')">
    Agenda
</x-nav-link>
<x-nav-link :href="route('dispatch.index')" :active="request()->routeIs('dispatch.*')">
    Dispatch
</x-nav-link>
@if (auth()->user()->technician)
    <x-nav-link :href="route('portal.index')" :active="request()->routeIs('portal.*')">
        Modo Técnico
    </x-nav-link>
@endif

<p class="mt-4 mb-1 px-3 text-[10px] font-semibold tracking-wider text-op-secondary/70 uppercase">
    Clientes
</p>
<x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">
    Clientes
</x-nav-link>

<p class="mt-4 mb-1 px-3 text-[10px] font-semibold tracking-wider text-op-secondary/70 uppercase">
    Equipe
</p>
<x-nav-link :href="route('technicians.index')" :active="request()->routeIs('technicians.*')">
    Técnicos
</x-nav-link>
<x-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.*')">
    Equipes
</x-nav-link>
