<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-950 text-slate-100">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
        <h1 class="text-2xl font-semibold">Sign in</h1>
        <p class="mt-2 text-sm text-slate-400">Access your company wellbeing dashboard</p>

        @if (session('info'))
            <div class="mt-4 rounded-lg border border-indigo-700/50 bg-indigo-950/40 p-3 text-sm text-indigo-100">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-800 bg-red-950/40 p-3 text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm text-slate-300">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 outline-none focus:border-indigo-500">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm text-slate-300">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 outline-none focus:border-indigo-500">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-950">
                Remember me
            </label>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium hover:bg-indigo-500">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            Нет аккаунта?
            <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300">Зарегистрироваться</a>
            ·
            <a href="{{ route('onboarding.welcome') }}" class="text-indigo-400 hover:text-indigo-300">О платформе</a>
        </p>
    </div>
</body>
</html>
