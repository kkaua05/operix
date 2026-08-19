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
        <header class="border-b border-op-border bg-op-surface">
            <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="text-sm font-semibold tracking-tight">OPERIX</span>
                </a>

                <div class="flex items-center gap-4">
                    <span class="hidden text-xs text-op-secondary sm:inline">{{ auth()->user()->name }}</span>

                    <a href="{{ route('profile.edit') }}"
                       class="text-xs text-op-secondary transition hover:text-op-primary">
                        Perfil
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-op-secondary transition hover:text-op-primary">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
