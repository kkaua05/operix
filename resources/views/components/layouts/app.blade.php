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
    <div x-data="{ mobileNavOpen: false }" class="flex h-full">
        <!-- Sidebar (desktop) -->
        <aside class="hidden w-60 shrink-0 flex-col border-r border-op-border bg-op-surface lg:flex">
            <div class="flex h-14 items-center border-b border-op-border px-5">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold tracking-tight">OPERIX</span>
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @include('components.layouts.partials.sidebar-nav')
            </nav>

            <div class="border-t border-op-border p-3">
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                    Perfil
                </x-nav-link>
            </div>
        </aside>

        <!-- Sidebar (mobile) -->
        <div x-show="mobileNavOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="absolute inset-0 bg-black/60" x-on:click="mobileNavOpen = false"></div>
            <aside class="relative flex h-full w-64 flex-col border-r border-op-border bg-op-surface">
                <div class="flex h-14 items-center justify-between border-b border-op-border px-5">
                    <span class="text-sm font-semibold tracking-tight">OPERIX</span>
                    <button type="button" x-on:click="mobileNavOpen = false" class="text-op-secondary hover:text-op-primary">
                        ✕
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    @include('components.layouts.partials.sidebar-nav')
                </nav>
            </aside>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 shrink-0 items-center justify-between border-b border-op-border bg-op-surface px-4 sm:px-6 lg:px-8">
                <button type="button" x-on:click="mobileNavOpen = true" class="text-op-secondary hover:text-op-primary lg:hidden">
                    ☰
                </button>

                <span class="hidden text-xs text-op-secondary lg:inline">{{ $title }}</span>

                <div class="flex items-center gap-4">
                    <span class="hidden text-xs text-op-secondary sm:inline">{{ auth()->user()->name }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-op-secondary transition hover:text-op-primary">
                            Sair
                        </button>
                    </form>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 overflow-y-auto px-4 py-8 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
