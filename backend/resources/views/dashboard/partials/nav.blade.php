<nav class="mb-8 flex gap-1 border-b border-slate-800">
    <a href="{{ route('dashboard.index') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.index', 'dashboard.employee') ? 'border-indigo-500 text-white' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
        Обзор
    </a>
    <a href="{{ route('dashboard.analysis') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.analysis*') ? 'border-indigo-500 text-white' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
        Анализ (LLM)
    </a>
    <a href="{{ route('dashboard.deep-analysis') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.deep-analysis*') ? 'border-indigo-500 text-white' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
        Глубокий анализ
    </a>
    <a href="{{ route('dashboard.integrations') }}"
       class="border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard.integrations*') ? 'border-indigo-500 text-white' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
        Интеграции
    </a>
</nav>
