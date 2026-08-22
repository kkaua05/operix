@props(['title' => 'Operix'])
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-op-bg text-op-primary antialiased">
    <div class="flex min-h-full flex-col">
        <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center justify-between border-b border-op-border bg-op-surface px-4">
            <a href="{{ route('portal.index') }}" wire:navigate class="text-sm font-semibold tracking-tight">
                OPERIX <span class="font-normal text-op-secondary">Técnico</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-op-secondary transition hover:text-op-primary">
                    Sair
                </button>
            </form>
        </header>

        <main class="mx-auto w-full max-w-lg flex-1 px-4 py-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
