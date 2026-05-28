@extends('layouts.onboarding')

@section('title', 'Добро пожаловать')

@section('content')
    <section class="mx-auto max-w-3xl text-center">
        <p class="inline-flex rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-xs font-medium uppercase tracking-wider text-violet-200">
            Платформа wellbeing для HR и команд
        </p>
        <h1 class="mt-6 text-4xl font-semibold leading-tight text-white sm:text-5xl">
            Следите за самочувствием команды — без лишней сложности
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-slate-400">
            Сотрудники проходят короткий ежедневный опрос через браузерное расширение.
            Вы получаете аналитику настроения, риски выгорания и динамику по отделам.
        </p>
    </section>

    <section class="mx-auto mt-12 grid max-w-4xl gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-white/5 bg-gradient-to-b from-violet-950/40 to-black/40 p-5">
            <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600/20 text-violet-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h2 class="font-medium text-white">Ежедневные check-in</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                Короткий опрос в расширении Chrome — mood, stress, поддержка команды.
            </p>
        </article>

        <article class="rounded-2xl border border-white/5 bg-gradient-to-b from-indigo-950/40 to-black/40 p-5">
            <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/20 text-indigo-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
            </div>
            <h2 class="font-medium text-white">Аналитика для HR</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                Dashboard с трендами, средними оценками и риском выгорания по сотрудникам.
            </p>
        </article>

        <article class="rounded-2xl border border-white/5 bg-gradient-to-b from-blue-950/40 to-black/40 p-5">
            <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/20 text-blue-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="font-medium text-white">Безопасное подключение</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                Каждой компании выдаётся секретный ключ для расширения. Данные изолированы.
            </p>
        </article>
    </section>

    <section class="mx-auto mt-14 max-w-xl text-center">
        <p class="text-sm text-slate-500">
            @guest
                Ознакомьтесь с возможностями платформы и подключите компанию, когда будете готовы.
            @else
                Чтобы начать, подключите компанию — мы создадим рабочее пространство и выдадим ключ для расширения.
            @endguest
        </p>

        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('onboarding.company.start') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-900/40 transition hover:from-violet-500 hover:to-indigo-500">
                <span class="text-lg leading-none">+</span>
                Подключить компанию
            </a>

            @guest
                <a href="{{ route('register') }}"
                   class="inline-flex rounded-xl border border-white/10 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-violet-500/40 hover:bg-violet-500/10">
                    Зарегистрироваться
                </a>
            @endguest
        </div>

        @guest
            <p class="mt-4 text-sm text-slate-500">
                Уже есть аккаунт?
                <a href="{{ route('login') }}" class="text-violet-300 hover:text-violet-200">Войти</a>
            </p>
        @endguest
    </section>
@endsection
