<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','CRM') — {{ $site['site_short_name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-extensions.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-pool.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
@php($isAccountant = auth()->user()->role === 'accountant')
@php($roleLabels = ['admin' => 'Администратор', 'manager' => 'Менеджер', 'accountant' => 'Бухгалтер'])
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="{{ $isAccountant ? route('admin.finance.index') : route('admin.dashboard') }}">
            <span>Γ</span>
            <div><strong>{{ $site['site_short_name'] }}</strong><small>{{ $isAccountant ? 'Бухгалтерия' : 'Pool CRM' }}</small></div>
        </a>

        <nav>
            @if($isAccountant)
                <div class="nav-caption">Финансы и учёт</div>
                <a class="{{ request()->routeIs('admin.finance.*')?'active':'' }}" href="{{ route('admin.finance.index') }}"><i class="bi bi-cash-stack"></i>Касса и платежи</a>
                <a class="{{ request()->routeIs('admin.orders.*')?'active':'' }}" href="{{ route('admin.orders.index') }}"><i class="bi bi-receipt"></i>Заказы</a>
                <a class="{{ request()->routeIs('admin.customers.*')?'active':'' }}" href="{{ route('admin.customers.index') }}"><i class="bi bi-people"></i>Клиенты</a>
                <a class="{{ request()->routeIs('admin.inventory.*')?'active':'' }}" href="{{ route('admin.inventory.index') }}"><i class="bi bi-box-seam"></i>Склад</a>
                <a class="{{ request()->routeIs('admin.staff.*')?'active':'' }}" href="{{ route('admin.staff.index') }}"><i class="bi bi-cash-coin"></i>Зарплата и персонал</a>
                <a class="{{ request()->routeIs('admin.reports.*')?'active':'' }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart-line"></i>Отчёты и KPI</a>
            @else
                <a class="{{ request()->routeIs('admin.dashboard')?'active':'' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid"></i>Обзор</a>

                <div class="nav-caption">Бассейн и проход</div>
                <a class="{{ request()->routeIs('admin.pool.*')?'active':'' }}" href="{{ route('admin.pool.index') }}"><i class="bi bi-water"></i>Бассейн и дорожки</a>
                <a class="{{ request()->routeIs('admin.access.*')?'active':'' }}" href="{{ route('admin.access.index') }}"><i class="bi bi-qr-code-scan"></i>СКУД и проход</a>
                <a class="{{ request()->routeIs('admin.schedule.*')?'active':'' }}" href="{{ route('admin.schedule.index') }}"><i class="bi bi-clock"></i>Расписание</a>
                <a class="{{ request()->routeIs('admin.bookings.*')?'active':'' }}" href="{{ route('admin.bookings.index') }}"><i class="bi bi-calendar2-check"></i>Записи</a>

                <div class="nav-caption">Клиенты и продажи</div>
                <a class="{{ request()->routeIs('admin.customers.*')?'active':'' }}" href="{{ route('admin.customers.index') }}"><i class="bi bi-people"></i>Клиенты</a>
                <a class="{{ request()->routeIs('admin.memberships.*')?'active':'' }}" href="{{ route('admin.memberships.index') }}"><i class="bi bi-person-vcard"></i>Членства и пакеты</a>
                <a class="{{ request()->routeIs('admin.leads.*')?'active':'' }}" href="{{ route('admin.leads.index') }}"><i class="bi bi-funnel"></i>Лиды</a>
                <a class="{{ request()->routeIs('admin.crm-plus.*')?'active':'' }}" href="{{ route('admin.crm-plus.index') }}"><i class="bi bi-kanban"></i>CRM+</a>
                <a class="{{ request()->routeIs('admin.orders.*')?'active':'' }}" href="{{ route('admin.orders.index') }}"><i class="bi bi-receipt"></i>Заказы</a>
                <a class="{{ request()->routeIs('admin.certificates.*')?'active':'' }}" href="{{ route('admin.certificates.index') }}"><i class="bi bi-gift"></i>Сертификаты</a>

                <div class="nav-caption">Финансы и товары</div>
                <a class="{{ request()->routeIs('admin.finance.*')?'active':'' }}" href="{{ route('admin.finance.index') }}"><i class="bi bi-cash-stack"></i>Касса и платежи</a>
                <a class="{{ request()->routeIs('admin.products.*')?'active':'' }}" href="{{ route('admin.products.index') }}"><i class="bi bi-ticket-perforated"></i>Товары и тарифы</a>
                <a class="{{ request()->routeIs('admin.inventory.*')?'active':'' }}" href="{{ route('admin.inventory.index') }}"><i class="bi bi-box-seam"></i>Склад</a>

                <div class="nav-caption">Персонал</div>
                <a class="{{ request()->routeIs('admin.trainers.*')?'active':'' }}" href="{{ route('admin.trainers.index') }}"><i class="bi bi-person-badge"></i>Тренеры</a>
                <a class="{{ request()->routeIs('admin.training-plans.*')?'active':'' }}" href="{{ route('admin.training-plans.index') }}"><i class="bi bi-clipboard2-pulse"></i>Планы тренировок</a>
                <a class="{{ request()->routeIs('admin.staff.*')?'active':'' }}" href="{{ route('admin.staff.index') }}"><i class="bi bi-calendar3"></i>Графики и зарплата</a>

                <div class="nav-caption">Управление</div>
                <a class="{{ request()->routeIs('admin.reports.*')?'active':'' }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart-line"></i>Отчёты и KPI</a>

                <div class="nav-caption">Сайт</div>
                <a class="{{ request()->routeIs('admin.slides.*')?'active':'' }}" href="{{ route('admin.slides.index') }}"><i class="bi bi-card-image"></i>Слайдер</a>
                <a class="{{ request()->routeIs('admin.news.*')?'active':'' }}" href="{{ route('admin.news.index') }}"><i class="bi bi-newspaper"></i>Новости</a>
                <a class="{{ request()->routeIs('admin.gallery.*')?'active':'' }}" href="{{ route('admin.gallery.index') }}"><i class="bi bi-images"></i>Фотогалерея</a>

                <div class="nav-caption">Настройки</div>
                <a class="{{ request()->routeIs('admin.settings.general*')?'active':'' }}" href="{{ route('admin.settings.general') }}"><i class="bi bi-sliders"></i>Настройки сайта</a>
                <a class="{{ request()->routeIs('admin.settings.contacts*')?'active':'' }}" href="{{ route('admin.settings.contacts') }}"><i class="bi bi-telephone"></i>Контакты и реквизиты</a>
                <a class="{{ request()->routeIs('admin.seo.*')?'active':'' }}" href="{{ route('admin.seo.index') }}"><i class="bi bi-search"></i>SEO</a>
            @endif
        </nav>

        <div class="sidebar-bottom">
            <a href="{{ route('home') }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Открыть сайт</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button><i class="bi bi-box-arrow-left"></i>Выйти</button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle" type="button" onclick="document.body.classList.toggle('sidebar-open')"><i class="bi bi-list"></i></button>
            <div>
                <small>@yield('eyebrow',$site['site_name'])</small>
                <h1>@yield('heading','CRM')</h1>
            </div>
            <div class="admin-user">
                <span>{{ mb_substr(auth()->user()->name,0,1) }}</span>
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ $roleLabels[auth()->user()->role] ?? auth()->user()->role }}</small>
                </div>
            </div>
        </header>

        <main class="admin-content">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded-4">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
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
