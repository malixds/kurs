@extends('layouts.onboarding')

@section('title', 'Вход')

@section('content')
    <div class="mx-auto flex max-w-md flex-col justify-center pt-8 lg:min-h-[50vh] lg:pt-12">
        <div class="ds-card">
            <h1 class="text-2xl font-bold tracking-tight text-[#111827]">Войти</h1>
            <p class="mt-2 text-sm text-[#5F6473]">Доступ к dashboard благополучия вашей компании</p>

            @if (session('info'))
                <div class="mt-4 rounded-xl border border-[rgba(111,99,255,0.2)] bg-[rgba(111,99,255,0.08)] p-3 text-sm text-[#4E44E5]">
                    {{ session('info') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-[#5F6473]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="ds-input">
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-[#5F6473]">Пароль</label>
                    <input id="password" name="password" type="password" required class="ds-input">
                </div>
                <label class="flex items-center gap-2 text-sm text-[#5F6473]">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#6F63FF] focus:ring-[#6F63FF]">
                    Запомнить меня
                </label>
                <button type="submit" class="ds-btn-primary w-full">
                    Войти
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#8C92A3]">
                Нет аккаунта?
                <a href="{{ route('register') }}" class="font-medium text-[#4E44E5] hover:text-[#6F63FF]">Зарегистрироваться</a>
                ·
                <a href="{{ route('onboarding.welcome') }}" class="font-medium text-[#4E44E5] hover:text-[#6F63FF]">О платформе</a>
            </p>
        </div>
    </div>
@endsection
