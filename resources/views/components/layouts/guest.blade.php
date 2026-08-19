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
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <div class="mb-8 flex flex-col items-center gap-1">
            <span class="text-xl font-semibold tracking-tight">OPERIX</span>
            <span class="text-xs text-op-secondary">Enterprise Field Service Management</span>
        </div>

        <div class="w-full max-w-sm rounded-xl border border-op-border bg-op-card p-8 shadow-xl shadow-black/20">
            {{ $slot }}
        </div>

        <p class="mt-8 text-xs text-op-secondary">&copy; {{ date('Y') }} Operix. Todos os direitos reservados.</p>
    </div>

    @livewireScripts
</body>
</html>
