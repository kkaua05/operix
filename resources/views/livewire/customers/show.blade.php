<div>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold">{{ $customer->name }}</h1>
                <x-badge :variant="$customer->status === 'active' ? 'success' : 'default'">
                    {{ $customer->status === 'active' ? 'Ativo' : 'Inativo' }}
                </x-badge>
            </div>
            <p class="text-xs text-op-secondary">
                {{ $customer->type === 'company' ? 'Pessoa jurídica' : 'Pessoa física' }}
                {{ $customer->document ? '· '.$customer->document : '' }}
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('customers.index') }}" wire:navigate>
                <x-button variant="secondary">Voltar</x-button>
            </a>
            @can('update', $customer)
                <a href="{{ route('customers.edit', $customer) }}" wire:navigate>
                    <x-button>Editar</x-button>
                </a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-6 border-b border-op-border">
        <nav class="-mb-px flex gap-6 overflow-x-auto text-sm">
            @foreach ([
                'resumo' => 'Resumo',
                'enderecos' => 'Endereços ('.$customer->addresses_count.')',
                'contatos' => 'Contatos ('.$customer->contacts_count.')',
                'equipamentos' => 'Equipamentos ('.$customer->equipment_count.')',
                'ordens' => 'Ordens ('.$customer->work_orders_count.')',
                'financeiro' => 'Financeiro',
                'historico' => 'Histórico',
            ] as $tab => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $tab }}')"
                    @class([
                        'whitespace-nowrap border-b-2 px-1 py-3 text-xs font-medium transition',
                        'border-op-accent text-op-primary' => $activeTab === $tab,
                        'border-transparent text-op-secondary hover:text-op-primary' => $activeTab !== $tab,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($activeTab === 'resumo')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Contato</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-op-secondary">E-mail</dt><dd>{{ $customer->email ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Telefone</dt><dd>{{ $customer->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Celular</dt><dd>{{ $customer->mobile_phone ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Dados cadastrais</h3>
                <dl class="space-y-2 text-sm">
                    @if ($customer->type === 'company')
                        <div class="flex justify-between"><dt class="text-op-secondary">Razão social</dt><dd>{{ $customer->legal_name ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Nome fantasia</dt><dd>{{ $customer->trading_name ?: '—' }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-op-secondary">Cliente desde</dt><dd>{{ $customer->created_at->format('d/m/Y') }}</dd></div>
                </dl>
            </div>

            @if ($customer->notes)
                <div class="rounded-xl border border-op-border bg-op-card p-5 sm:col-span-2">
                    <h3 class="mb-2 text-xs font-semibold tracking-wider text-op-secondary uppercase">Observações</h3>
                    <p class="text-sm whitespace-pre-line text-op-secondary">{{ $customer->notes }}</p>
                </div>
            @endif
        </div>
    @elseif ($activeTab === 'enderecos')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            @livewire('customers.address-manager', ['customer' => $customer], key('addr-'.$customer->id))
        </div>
    @elseif ($activeTab === 'contatos')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            @livewire('customers.contact-manager', ['customer' => $customer], key('contact-'.$customer->id))
        </div>
    @elseif ($activeTab === 'equipamentos')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            @livewire('customers.equipment-manager', ['customer' => $customer], key('equip-'.$customer->id))
        </div>
    @elseif ($activeTab === 'ordens')
        <x-empty-state
            title="Módulo de Ordens de Serviço em construção"
            description="O gerenciamento de ordens de serviço deste cliente estará disponível em uma próxima fase do roadmap."
        />
    @elseif ($activeTab === 'financeiro')
        <x-empty-state
            title="Módulo Financeiro em construção"
            description="O histórico financeiro deste cliente estará disponível em uma próxima fase do roadmap."
        />
    @elseif ($activeTab === 'historico')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Histórico</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between border-b border-op-border pb-2">
                    <span class="text-op-secondary">Cadastro criado</span>
                    <span>{{ $customer->created_at->format('d/m/Y H:i') }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-op-secondary">Última atualização</span>
                    <span>{{ $customer->updated_at->format('d/m/Y H:i') }}</span>
                </li>
            </ul>
            <p class="mt-4 text-xs text-op-secondary">
                O log de auditoria completo (quem alterou o quê) estará disponível quando o módulo de Auditoria for implementado.
            </p>
        </div>
    @endif
</div>
