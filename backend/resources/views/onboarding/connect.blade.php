@extends('layouts.onboarding')

@section('title', 'Подключение компании')

@section('content')
    <div class="mx-auto max-w-lg pt-8 lg:pt-12">
        @auth
            @if (auth()->user()->companies()->exists())
                <a href="{{ route('companies.index') }}" class="text-sm font-medium text-[#4E44E5] transition hover:text-[#6F63FF]">
                    ← Мои компании
                </a>
            @else
                <a href="{{ route('onboarding.welcome') }}" class="text-sm font-medium text-[#4E44E5] transition hover:text-[#6F63FF]">
                    ← Назад
                </a>
            @endif
        @else
            <a href="{{ route('onboarding.welcome') }}" class="text-sm font-medium text-[#4E44E5] transition hover:text-[#6F63FF]">
                ← Назад
            </a>
        @endauth

        <div class="ds-card mt-6">
            <h1 class="text-3xl font-bold tracking-tight text-[#111827]">Подключить компанию</h1>
            <p class="mt-2 text-sm leading-relaxed text-[#5F6473]">
                Укажите название организации. После создания вы получите секретный ключ для браузерного расширения.
            </p>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('onboarding.company.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-[#5F6473]">Название компании</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                           placeholder="Например, Acme Remote Corp"
                           class="ds-input">
                </div>

                <button type="submit" class="ds-btn-primary w-full">
                    Создать компанию
                </button>
            </form>
        </div>
    </div>
@endsection
