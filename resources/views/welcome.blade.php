<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operix — Gestão de Field Service</title>
    <meta name="description" content="Operix é a plataforma completa para gerenciar ordens de serviço, técnicos de campo, SLA, estoque e financeiro em um único lugar.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-op-bg text-op-primary antialiased">
    <header class="border-b border-op-border">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <span class="text-sm font-semibold tracking-tight">OPERIX</span>

            <nav class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-xs text-op-secondary hover:text-op-primary">Ir para o dashboard</a>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-xs text-op-secondary hover:text-op-primary">Entrar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <section class="mx-auto max-w-4xl px-6 py-24 text-center">
            <p class="mb-4 text-xs font-semibold tracking-widest text-op-accent uppercase">Field Service Management</p>
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                A operação de campo da sua empresa, sob controle.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-base text-op-secondary">
                Ordens de serviço, técnicos, SLA, agenda, estoque e financeiro em uma única
                plataforma multiempresa — do primeiro chamado até a nota fiscal.
            </p>

            <div class="mt-10 flex items-center justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-button>Ir para o dashboard</x-button>
                    </a>
                @else
                    <a href="{{ route('login') }}" wire:navigate>
                        <x-button>Entrar na plataforma</x-button>
                    </a>
                @endauth
            </div>
        </section>

        <section class="border-t border-op-border">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-6 py-16 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => '🧾', 'title' => 'Ordens de serviço', 'text' => 'Ciclo completo — da abertura à conclusão, com timeline e SLA em tempo real.'],
                    ['icon' => '🧑‍🔧', 'title' => 'Técnicos e despacho', 'text' => 'Central de despacho com arrastar-e-soltar e portal mobile para o técnico em campo.'],
                    ['icon' => '📦', 'title' => 'Estoque e financeiro', 'text' => 'Consumo de materiais, movimentações e margem calculada automaticamente por OS.'],
                    ['icon' => '📊', 'title' => 'Relatórios e automação', 'text' => 'Indicadores operacionais, SLA e produtividade, com alertas automáticos.'],
                ] as $feature)
                    <div class="rounded-xl border border-op-border bg-op-card p-6">
                        <span class="text-2xl">{{ $feature['icon'] }}</span>
                        <h3 class="mt-3 text-sm font-semibold">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-xs text-op-secondary">{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="border-t border-op-border">
        <div class="mx-auto max-w-6xl px-6 py-6 text-center text-xs text-op-secondary">
            &copy; {{ now()->year }} Operix. Todos os direitos reservados.
        </div>
    </footer>
</body>
</html>
