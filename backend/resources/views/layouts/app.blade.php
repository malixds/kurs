<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ds-page ds-atmosphere relative min-h-screen antialiased">
    <div class="relative z-10">
        <nav class="mx-auto max-w-7xl px-6 py-5 lg:px-10">
            <div class="flex items-center justify-between rounded-2xl border border-white/60 bg-white/80 px-5 py-4 shadow-[0_10px_30px_rgba(0,0,0,0.05)] backdrop-blur-sm">
                <div>
                    <a href="{{ route('dashboard.index') }}" class="text-lg font-bold tracking-tight text-[#111827]">
                        {{ config('app.name') }}
                    </a>
                    <p class="text-xs font-medium text-[#8C92A3]">Аналитика благополучия команд</p>
                </div>
                <div class="flex items-center gap-3">
                    @if (auth()->user()->companies()->exists())
                        <a href="{{ route('companies.index') }}" class="ds-nav-link hidden sm:inline">
                            Компании
                        </a>
                    @endif
                    <span class="hidden text-sm text-[#5F6473] sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ds-btn-secondary !min-h-[2.5rem] !px-3 !py-1.5 text-sm">
                            Выйти
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-6 py-8 lg:px-10">
            @yield('content')
        </main>
    </div>
</body>
</html>
