<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#07070f] text-slate-100">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-32 top-0 h-96 w-96 rounded-full bg-violet-700/20 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-80 w-80 rounded-full bg-indigo-600/15 blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/10 bg-black/40 p-8 shadow-2xl backdrop-blur">
        <h1 class="text-2xl font-semibold">Создать аккаунт</h1>
        <p class="mt-2 text-sm text-slate-400">После регистрации вы подключите компанию</p>

        @if (session('info'))
            <div class="mt-4 rounded-lg border border-violet-700/50 bg-violet-950/40 p-3 text-sm text-violet-100">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-800 bg-red-950/40 p-3 text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-sm text-slate-300">Имя</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-white/10 bg-black/50 px-3 py-2 outline-none focus:border-violet-500">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm text-slate-300">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-white/10 bg-black/50 px-3 py-2 outline-none focus:border-violet-500">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm text-slate-300">Пароль</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-white/10 bg-black/50 px-3 py-2 outline-none focus:border-violet-500">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm text-slate-300">Подтверждение пароля</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full rounded-lg border border-white/10 bg-black/50 px-3 py-2 outline-none focus:border-violet-500">
            </div>
            <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2 font-medium hover:from-violet-500 hover:to-indigo-500">
                Зарегистрироваться
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            Уже есть аккаунт?
            <a href="{{ route('login') }}" class="text-violet-300 hover:text-violet-200">Войти</a>
            ·
            <a href="{{ route('onboarding.welcome') }}" class="text-violet-300 hover:text-violet-200">О платформе</a>
        </p>
    </div>
</body>
</html>
