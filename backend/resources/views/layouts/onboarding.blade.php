<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Wellbeing Monitor') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="onboarding-page min-h-screen bg-[#07070f] text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-32 top-0 h-96 w-96 rounded-full bg-violet-700/20 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-80 w-80 rounded-full bg-indigo-600/15 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-blue-700/10 blur-3xl"></div>
    </div>

    <div class="relative z-10 flex min-h-screen flex-col">
        <header class="border-b border-white/5 bg-black/20 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
                <div>
                    <p class="text-lg font-semibold tracking-tight text-white">{{ config('app.name') }}</p>
                    <p class="text-xs text-violet-300/70">Мониторинг благополучия удалённых команд</p>
                </div>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('companies.index') }}"
                           class="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 transition hover:border-violet-500/40 hover:bg-violet-500/10">
                            Мои компании
                        </a>
                        <span class="hidden text-sm text-slate-400 sm:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 transition hover:border-violet-500/40 hover:bg-violet-500/10">
                                Выйти
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 transition hover:border-violet-500/40 hover:bg-violet-500/10">
                            Войти
                        </a>
                        <a href="{{ route('register') }}"
                           class="rounded-lg bg-violet-600/80 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-violet-500">
                            Регистрация
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
