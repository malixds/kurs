@extends('layouts.onboarding')

@section('title', 'Добро пожаловать')

@section('content')
    <section class="mx-auto max-w-4xl pt-8 text-center lg:pt-16">
        <p class="ds-badge mx-auto">
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#6F63FF]"></span>
            Платформа wellbeing для HR и удалённых команд
        </p>

        <h1 class="mt-8 text-4xl font-bold leading-[0.95] tracking-[-0.04em] text-[#111827] sm:text-5xl lg:text-[4.5rem]">
            Следите за <span class="ds-gradient-text">самочувствием</span> команды — без лишней сложности
        </h1>

        <p class="mx-auto mt-6 max-w-2xl text-base leading-[1.7] text-[#5F6473] sm:text-lg">
            Сотрудники проходят короткий ежедневный опрос через браузерное расширение.
            Вы получаете аналитику настроения, риски выгорания и динамику по отделам.
        </p>

        <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a href="{{ route('onboarding.company.start') }}" class="ds-btn-primary">
                <span class="text-lg leading-none">+</span>
                Подключить компанию
            </a>
            @guest
                <a href="{{ route('register') }}" class="ds-btn-secondary">
                    Зарегистрироваться
                </a>
            @endguest
        </div>

        @guest
            <p class="mt-6 text-sm text-[#8C92A3]">
                Уже есть аккаунт?
                <a href="{{ route('login') }}" class="font-medium text-[#4E44E5] hover:text-[#6F63FF]">Войти</a>
            </p>
        @endguest

        <div class="mt-10 flex flex-wrap items-center justify-center gap-8 sm:gap-12">
            <div class="text-center">
                <p class="text-3xl font-bold tracking-tight text-[#111827]">2 мин</p>
                <p class="mt-1 text-xs font-medium text-[#8C92A3]">на ежедневный check-in</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold tracking-tight text-[#111827]">24/7</p>
                <p class="mt-1 text-xs font-medium text-[#8C92A3]">доступ к аналитике HR</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold tracking-tight text-[#111827]">100%</p>
                <p class="mt-1 text-xs font-medium text-[#8C92A3]">изоляция данных компании</p>
            </div>
        </div>
    </section>

    <section class="relative mx-auto mt-16 hidden h-[280px] max-w-5xl sm:block lg:mt-20 lg:h-[320px]" aria-hidden="true">
        <div class="ds-floating-card ds-float-a left-[8%] top-[12%] w-[200px] text-left">
            <p class="text-xs font-medium text-[#8C92A3]">Средний mood</p>
            <p class="mt-1 text-2xl font-bold text-[#111827]">7.8</p>
            <p class="mt-2 text-xs text-emerald-600">↑ 12% за неделю</p>
        </div>
        <div class="ds-floating-card ds-float-b right-[6%] top-[8%] w-[220px] text-left">
            <p class="text-xs font-medium text-[#8C92A3]">Риск выгорания</p>
            <p class="mt-1 text-2xl font-bold text-[#111827]">Низкий</p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#F7F8FC]">
                <div class="h-full w-[28%] rounded-full bg-gradient-to-r from-[#6F63FF] to-[#B7A7FF]"></div>
            </div>
        </div>
        <div class="ds-floating-card ds-float-c bottom-[8%] left-[32%] w-[240px] text-left">
            <p class="text-xs font-medium text-[#8C92A3]">Check-in сегодня</p>
            <p class="mt-1 text-2xl font-bold text-[#111827]">86%</p>
            <p class="mt-2 text-xs text-[#5F6473]">12 из 14 сотрудников</p>
        </div>
    </section>

    <section id="how" class="mx-auto mt-20 grid max-w-5xl gap-6 sm:grid-cols-3 lg:mt-24">
        <article class="ds-card text-left">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[rgba(111,99,255,0.1)] text-[#6F63FF]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-[#111827]">Ежедневные check-in</h2>
            <p class="mt-2 text-sm leading-relaxed text-[#5F6473]">
                Короткий опрос в расширении Chrome — mood, stress, поддержка команды.
            </p>
        </article>

        <article class="ds-card text-left">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[rgba(111,99,255,0.1)] text-[#6F63FF]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-[#111827]">Аналитика для HR</h2>
            <p class="mt-2 text-sm leading-relaxed text-[#5F6473]">
                Dashboard с трендами, средними оценками и риском выгорания по сотрудникам.
            </p>
        </article>

        <article class="ds-card text-left">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[rgba(111,99,255,0.1)] text-[#6F63FF]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-[#111827]">Безопасное подключение</h2>
            <p class="mt-2 text-sm leading-relaxed text-[#5F6473]">
                Каждой компании выдаётся секретный ключ для расширения. Данные изолированы.
            </p>
        </article>
    </section>

    <section class="mx-auto mt-20 max-w-xl rounded-[1.75rem] bg-white/70 p-8 text-center shadow-[0_10px_30px_rgba(0,0,0,0.04)] backdrop-blur-sm lg:mt-28">
        <p class="text-sm text-[#5F6473]">
            @guest
                Ознакомьтесь с возможностями платформы и подключите компанию, когда будете готовы.
            @else
                Чтобы начать, подключите компанию — мы создадим рабочее пространство и выдадим ключ для расширения.
            @endguest
        </p>
        <a href="{{ route('onboarding.company.start') }}" class="ds-btn-primary mt-6">
            Начать подключение
        </a>
    </section>
@endsection
