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

@can('viewAny', \App\Models\Product::class)
    <p class="mt-4 mb-1 px-3 text-[10px] font-semibold tracking-wider text-op-secondary/70 uppercase">
        Estoque
    </p>
    <x-nav-link :href="route('inventory.products.index')" :active="request()->routeIs('inventory.products.*')">
        Produtos
    </x-nav-link>
    <x-nav-link :href="route('inventory.categories.index')" :active="request()->routeIs('inventory.categories.*')">
        Categorias
    </x-nav-link>
    <x-nav-link :href="route('inventory.suppliers.index')" :active="request()->routeIs('inventory.suppliers.*')">
        Fornecedores
    </x-nav-link>
@endcan

@can('viewAny', \App\Models\FinancialTransaction::class)
    <p class="mt-4 mb-1 px-3 text-[10px] font-semibold tracking-wider text-op-secondary/70 uppercase">
        Financeiro
    </p>
    <x-nav-link :href="route('financial.index')" :active="request()->routeIs('financial.*')">
        Lançamentos
    </x-nav-link>
@endcan

@can('reports.view')
    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
        Relatórios
    </x-nav-link>
@endcan

@can('settings.manage')
    <p class="mt-4 mb-1 px-3 text-[10px] font-semibold tracking-wider text-op-secondary/70 uppercase">
        Configurações
    </p>
    <x-nav-link :href="route('settings.notifications')" :active="request()->routeIs('settings.notifications')">
        Notificações
    </x-nav-link>
@endcan
