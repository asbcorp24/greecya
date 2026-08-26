<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ $site['site_short_name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-pool.css') }}" rel="stylesheet">
    <style>
        body{background:#f4f7fb}.workspace-header{background:#fff;border-bottom:1px solid #e7ecf3;position:sticky;top:0;z-index:20}.workspace-wrap{max-width:1500px;margin:auto;padding:24px}.workspace-brand{font-size:1.25rem;font-weight:800}.workspace-brand span{display:inline-grid;place-items:center;width:42px;height:42px;border-radius:14px;background:#0b5ed7;color:#fff;font-family:Georgia;font-size:28px}.big-search{font-size:1.35rem;padding:1rem 1.2rem}.status-tile{border-radius:20px;padding:18px;background:#fff;border:1px solid #e7ecf3;height:100%}.customer-photo{width:112px;height:112px;border-radius:28px;object-fit:cover;background:#e9eef7;display:grid;place-items:center;font-size:42px;font-weight:800;color:#6b778c}
    </style>
    @stack('styles')
</head>
<body>
<header class="workspace-header">
    <div class="container-fluid px-4 py-3 d-flex justify-content-between align-items-center">
        <div class="workspace-brand d-flex align-items-center gap-2">
            <span>Γ</span>
            <div>
                @yield('workspace_name')
                <small class="d-block text-muted fw-normal">{{ $site['site_short_name'] }}</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 gap-md-3">
            @if(auth()->user()->hasPermission('sales.pos') && request()->routeIs('reception*'))
                <a class="btn btn-success" href="{{ route('reception.sales.create') }}"><i class="bi bi-cart-plus"></i> <span class="d-none d-md-inline">Первичная продажа</span></a>
            @endif
            <a class="btn btn-outline-primary" href="{{ route('help.index') }}"><i class="bi bi-question-circle"></i> <span class="d-none d-md-inline">Справка</span></a>
            <div class="text-end d-none d-sm-block">
                <strong>{{ auth()->user()->name }}</strong>
                <small class="d-block text-muted">{{ config('access.roles.'.auth()->user()->role, auth()->user()->role) }}</small>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Выйти</button>
            </form>
        </div>
    </div>
</header>
<main class="workspace-wrap">
    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-sm">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
