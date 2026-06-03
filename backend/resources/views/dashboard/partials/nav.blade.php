<nav class="mb-8 flex flex-wrap gap-1 border-b border-black/[0.06]">
    <a href="{{ route('dashboard.index') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.index', 'dashboard.employee') ? 'border-[#6F63FF] text-[#111827]' : 'border-transparent ds-text-secondary hover:text-[#111827]' }}">
        Обзор
    </a>
    <a href="{{ route('dashboard.analysis') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.analysis', 'dashboard.analysis.*') ? 'border-[#6F63FF] text-[#111827]' : 'border-transparent ds-text-secondary hover:text-[#111827]' }}">
        Анализ (LLM)
    </a>
    <a href="{{ route('dashboard.recommendations.index') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.recommendations*') ? 'border-[#6F63FF] text-[#111827]' : 'border-transparent ds-text-secondary hover:text-[#111827]' }}">
        История LLM
    </a>
    <a href="{{ route('dashboard.deep-analysis') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.deep-analysis', 'dashboard.deep-analysis.*') ? 'border-[#6F63FF] text-[#111827]' : 'border-transparent ds-text-secondary hover:text-[#111827]' }}">
        Глубокий анализ
    </a>
    <a href="{{ route('dashboard.integrations') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.integrations*') ? 'border-[#6F63FF] text-[#111827]' : 'border-transparent ds-text-secondary hover:text-[#111827]' }}">
        Интеграции
    </a>
</nav>
