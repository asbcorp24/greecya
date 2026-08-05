<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Бассейн с морской водой и SPA-комплекс Греция в Васильево. Свободное плавание, тренировки, массаж, сауна и соляная комната.">
    <title>@yield('title', 'Комплекс Греция — бассейн и SPA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Prata&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<div class="promo-bar">
    <div class="container d-flex justify-content-center gap-2 text-center">
        <i class="bi bi-gift"></i>
        <span>Подпишитесь и напишите слово <strong>«БАССЕЙН»</strong> — сеанс соляной комнаты в подарок</span>
    </div>
</div>
<nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <span class="brand-mark"><span>Γ</span></span>
            <span><strong>Греция</strong><small>бассейн · SPA</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#services">Услуги</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('catalog.index') }}">Билеты и абонементы</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#contacts">Контакты</a></li>
                <li class="nav-item"><a class="nav-link phone-link" href="tel:+79655877799"><i class="bi bi-telephone"></i> {{ config('app.complex.phone') }}</a></li>
                <li class="nav-item ms-lg-2"><a class="btn btn-primary rounded-pill px-4" href="{{ route('booking.index') }}">Записаться</a></li>
            </ul>
        </div>
    </div>
</nav>

@if ($errors->any())
    <div class="container mt-3">
        <div class="alert alert-danger rounded-4 border-0 shadow-sm">
            <strong>Проверьте заполнение формы:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
@if (session('success'))
    <div class="container mt-3"><div class="alert alert-success rounded-4 border-0 shadow-sm">{{ session('success') }}</div></div>
@endif

<main>@yield('content')</main>

<footer class="site-footer" id="contacts">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-5">
                <a class="navbar-brand text-white d-inline-flex align-items-center gap-2 mb-3" href="{{ route('home') }}"><span class="brand-mark brand-mark-light"><span>Γ</span></span><span><strong>Греция</strong><small>бассейн · SPA</small></span></a>
                <p class="text-white-50 mb-0">Отдых, движение и восстановление в бассейне с морской водой и SPA-пространстве.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Разделы</h6>
                <a href="{{ route('home') }}#services">Услуги</a>
                <a href="{{ route('booking.index') }}">Онлайн-запись</a>
                <a href="{{ route('catalog.index') }}">Абонементы</a>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Документы</h6>
                <a href="{{ route('privacy') }}">Конфиденциальность</a>
                <a href="{{ route('offer') }}">Публичная оферта</a>
                <a href="{{ route('login') }}">Для сотрудников</a>
            </div>
            <div class="col-lg-3">
                <h6>Контакты</h6>
                <a href="tel:+79655877799" class="fs-5 text-white">{{ config('app.complex.phone') }}</a>
                <p class="text-white-50 mt-2 mb-2">{{ config('app.complex.address') }}</p>
                <p class="mb-0"><i class="bi bi-at"></i> {{ config('app.complex.social') }}</p>
            </div>
        </div>
        <hr class="border-light border-opacity-10 my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small text-white-50">
            <span>© {{ date('Y') }} Комплекс «Греция»</span>
            <span>Информация на сайте не является медицинской рекомендацией.</span>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
