<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <nav class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <div>
                <a href="{{ route('dashboard.index') }}" class="text-lg font-semibold text-white">
                    {{ config('app.name') }}
                </a>
                <p class="text-sm text-slate-400">Remote team wellbeing analytics</p>
            </div>
            <div class="flex items-center gap-4">
                @if (auth()->user()->companies()->exists())
                    <a href="{{ route('companies.index') }}" class="text-sm text-slate-300 hover:text-white">
                        Компании
                    </a>
                @endif
                <span class="text-sm text-slate-300">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm hover:bg-slate-800">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-8">
        @yield('content')
    </main>
</body>
</html>
