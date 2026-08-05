<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'CRM') — Греция</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            <span>Γ</span>
            <div>
                <strong>Греция</strong>
                <small>CRM</small>
            </div>
        </a>

        <nav>
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <i class="bi bi-grid"></i>
                Обзор
            </a>

            <a class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}"
               href="{{ route('admin.bookings.index') }}">
                <i class="bi bi-calendar2-check"></i>
                Записи
            </a>

            <a class="{{ request()->routeIs('admin.schedule.*') ? 'active' : '' }}"
               href="{{ route('admin.schedule.index') }}">
                <i class="bi bi-clock"></i>
                Расписание
            </a>

            <a class="{{ request()->routeIs('admin.leads.*') ? 'active' : '' }}"
               href="{{ route('admin.leads.index') }}">
                <i class="bi bi-funnel"></i>
                Лиды
            </a>

            <a class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
               href="{{ route('admin.customers.index') }}">
                <i class="bi bi-people"></i>
                Клиенты
            </a>

            <a class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
               href="{{ route('admin.orders.index') }}">
                <i class="bi bi-receipt"></i>
                Заказы
            </a>

            <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
               href="{{ route('admin.products.index') }}">
                <i class="bi bi-ticket-perforated"></i>
                Товары
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="{{ route('home') }}" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i>
                Открыть сайт
            </a>

            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-left"></i>
                    Выйти
                </button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle"
                    type="button"
                    onclick="document.body.classList.toggle('sidebar-open')"
                    aria-label="Открыть меню">
                <i class="bi bi-list"></i>
            </button>

            <div>
                <small>@yield('eyebrow', 'Комплекс Греция')</small>
                <h1>@yield('heading', 'CRM')</h1>
            </div>

            <div class="admin-user">
                <span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->role === 'admin' ? 'Администратор' : 'Менеджер' }}</small>
                </div>
            </div>
        </header>

        <main class="admin-content">
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
