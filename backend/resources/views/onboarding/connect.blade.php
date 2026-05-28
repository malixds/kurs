@extends('layouts.onboarding')

@section('title', 'Подключение компании')

@section('content')
    <div class="mx-auto max-w-lg">
        @auth
            @if (auth()->user()->companies()->exists())
                <a href="{{ route('companies.index') }}" class="text-sm text-violet-300/80 transition hover:text-violet-200">
                    ← Мои компании
                </a>
            @else
                <a href="{{ route('onboarding.welcome') }}" class="text-sm text-violet-300/80 transition hover:text-violet-200">
                    ← Назад
                </a>
            @endif
        @else
            <a href="{{ route('onboarding.welcome') }}" class="text-sm text-violet-300/80 transition hover:text-violet-200">
                ← Назад
            </a>
        @endauth

        <h1 class="mt-4 text-3xl font-semibold text-white">Подключить компанию</h1>
        <p class="mt-2 text-sm text-slate-400">
            Укажите название организации. После создания вы получите секретный ключ для браузерного расширения.
        </p>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-800/60 bg-red-950/30 p-4 text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.company.store') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="mb-2 block text-sm text-slate-300">Название компании</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       placeholder="Например, Acme Remote Corp"
                       class="w-full rounded-xl border border-white/10 bg-black/40 px-4 py-3 text-white outline-none transition placeholder:text-slate-600 focus:border-violet-500/60 focus:ring-2 focus:ring-violet-500/20">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3 font-semibold text-white transition hover:from-violet-500 hover:to-indigo-500">
                Создать компанию
            </button>
        </form>
    </div>
@endsection
