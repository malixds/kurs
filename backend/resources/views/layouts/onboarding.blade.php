<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Wellbeing Monitor') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ds-page onboarding-page ds-atmosphere relative min-h-screen antialiased">
    <div class="relative z-10 flex min-h-screen flex-col">
        <header class="mx-auto w-full max-w-7xl px-6 py-5 lg:px-10 lg:py-6">
            <div class="flex h-[4.5rem] items-center justify-between">
                <a href="{{ route('onboarding.welcome') }}" class="shrink-0">
                    <p class="text-lg font-bold tracking-tight text-[#111827]">{{ config('app.name') }}</p>
                    <p class="text-xs font-medium text-[#8C92A3]">Мониторинг благополучия команд</p>
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('companies.index') }}" class="ds-btn-secondary hidden !min-h-[2.75rem] !px-4 !py-2 text-sm sm:inline-flex">
                            Мои компании
                        </a>
                        <span class="hidden text-sm text-[#5F6473] lg:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="ds-btn-secondary !min-h-[2.75rem] !px-4 !py-2 text-sm">
                                Выйти
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="ds-nav-link hidden sm:inline">Войти</a>
                        <a href="{{ route('register') }}" class="ds-btn-primary !min-h-[2.75rem] !px-4 !py-2 text-sm">
                            Регистрация
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-6 pb-16 lg:px-10 lg:pb-24">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
