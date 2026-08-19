<x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Dashboard
</x-nav-link>

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
