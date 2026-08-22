<!doctype html>
<html lang="ru">
<head>
    @php
        $sectionTitle = trim($__env->yieldContent('title'));
        $dynamicTitle = trim($__env->yieldContent('seo_title'));
        $dynamicDescription = trim($__env->yieldContent('seo_description'));
        $dynamicImage = trim($__env->yieldContent('seo_image'));
        $pageTitle = $dynamicTitle ?: ($seoPage?->meta_title ?: ($sectionTitle ?: $site['seo_default_title']));
        $pageDescription = $dynamicDescription ?: ($seoPage?->meta_description ?: $site['seo_default_description']);
        $pageKeywords = $seoPage?->meta_keywords ?: $site['seo_default_keywords'];
        $ogTitle = $seoPage?->og_title ?: $pageTitle;
        $ogDescription = $seoPage?->og_description ?: $pageDescription;
        $ogImagePath = $dynamicImage ?: ($seoPage?->og_image_path ?: ($site['default_og_image'] ?? ''));
        $ogImage = $ogImagePath ? (str_starts_with($ogImagePath, 'http') ? $ogImagePath : Storage::url($ogImagePath)) : null;
        $canonical = $seoPage?->canonical_url ?: url()->current();
        $robots = $seoPage?->robots ?: (($site['seo_allow_indexing'] ?? true) ? 'index,follow' : 'noindex,nofollow');
        $phoneHref = preg_replace('/[^\d+]/', '', (string) ($site['phone'] ?? ''));
        $mapUrl = $site['map_url'] ?: 'https://yandex.ru/maps/?text='.urlencode($site['address_full'] ?? '');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if($pageKeywords)<meta name="keywords" content="{{ $pageKeywords }}">@endif
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $site['site_name'] }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif
    @if(!empty($site['favicon']))<link rel="icon" href="{{ Storage::url($site['favicon']) }}">@endif
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0b5ed7">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $site['site_short_name'] }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/pwa-192.svg') }}">
    @if($seoPage?->schema_json)<script type="application/ld+json">{!! $seoPage->schema_json !!}</script>@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Prata&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
    <link href="{{ asset('css/extensions.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
@if($site['promo_enabled'] ?? false)
<div class="promo-bar"><div class="container d-flex justify-content-center gap-2 text-center"><i class="bi bi-gift"></i><span>{{ $site['promo_text'] }}</span></div></div>
@endif
<nav class="navbar navbar-expand-xl navbar-light site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            @if(!empty($site['site_logo']))<img src="{{ Storage::url($site['site_logo']) }}" alt="{{ $site['site_name'] }}" style="width:48px;height:48px;object-fit:contain">@else<span class="brand-mark"><span>Γ</span></span>@endif
            <span><strong>{{ $site['site_short_name'] }}</strong><small>{{ $site['site_tagline'] }}</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-xl-center gap-xl-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#services">Услуги</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('news.index') }}">Новости</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('gallery.index') }}">Фотогалерея</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('catalog.index') }}">Билеты</a></li>
                <li class="nav-item"><a class="nav-link phone-link" href="tel:{{ $phoneHref }}"><i class="bi bi-telephone"></i> {{ $site['phone'] }}</a></li>
                @auth
                    <li class="nav-item ms-xl-1"><a class="btn btn-outline-primary rounded-pill px-3" href="{{ auth()->user()->role === 'customer' ? route('account.dashboard') : (auth()->user()->role === 'director' ? route('admin.director.dashboard') : route('admin.dashboard')) }}"><i class="bi bi-person-circle me-1"></i> Кабинет</a></li>
                @else
                    <li class="nav-item ms-xl-1"><a class="nav-link" href="{{ route('login') }}">Войти</a></li>
                @endauth
                <li class="nav-item ms-xl-1"><a class="btn btn-primary rounded-pill px-4" href="{{ route('booking.index') }}">Записаться</a></li>
            </ul>
        </div>
    </div>
</nav>
@if ($errors->any())<div class="container mt-3"><div class="alert alert-danger rounded-4 border-0 shadow-sm"><strong>Проверьте заполнение формы:</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
@if (session('success'))<div class="container mt-3"><div class="alert alert-success rounded-4 border-0 shadow-sm">{{ session('success') }}</div></div>@endif
<main>@yield('content')</main>
<footer class="site-footer" id="contacts">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="navbar-brand text-white d-inline-flex align-items-center gap-2 mb-3" href="{{ route('home') }}"><span class="brand-mark brand-mark-light"><span>Γ</span></span><span><strong>{{ $site['site_short_name'] }}</strong><small>{{ $site['site_tagline'] }}</small></span></a>
                <p class="text-white-50 mb-0">{{ $site['footer_text'] }}</p>
            </div>
            <div class="col-6 col-lg-2"><h6>Разделы</h6><a href="{{ route('home') }}#services">Услуги</a><a href="{{ route('news.index') }}">Новости</a><a href="{{ route('gallery.index') }}">Фотогалерея</a><a href="{{ route('booking.index') }}">Онлайн-запись</a></div>
            <div class="col-6 col-lg-2"><h6>Клиентам</h6><a href="{{ route('catalog.index') }}">Билеты и сертификаты</a><a href="{{ route('account.register') }}">Личный кабинет</a><a href="{{ route('privacy') }}">Конфиденциальность</a><a href="{{ route('offer') }}">Публичная оферта</a></div>
            <div class="col-lg-4">
                <h6>Контакты</h6>
                <a href="tel:{{ $phoneHref }}" class="fs-5 text-white">{{ $site['phone'] }}</a>
                @if($site['phone_alt'])<a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone_alt']) }}">{{ $site['phone_alt'] }}</a>@endif
                <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>
                <p class="text-white-50 mt-2 mb-2">{{ $site['address_full'] }}</p>
                <p class="small text-white-50 mb-2">{{ $site['working_hours_weekdays'] }}<br>{{ $site['working_hours_weekends'] }}</p>
                <a href="{{ $mapUrl }}" target="_blank" rel="noopener">Открыть на карте</a>
                <div class="d-flex gap-3 mt-3">
                    @if($site['social_vk'])<a href="{{ $site['social_vk'] }}" target="_blank" aria-label="ВКонтакте"><i class="bi bi-vk"></i></a>@endif
                    @if($site['social_telegram'])<a href="{{ $site['social_telegram'] }}" target="_blank" aria-label="Telegram"><i class="bi bi-telegram"></i></a>@endif
                    @if($site['social_whatsapp'])<a href="{{ $site['social_whatsapp'] }}" target="_blank" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
                    @if($site['social_instagram'])<a href="{{ $site['social_instagram'] }}" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a>@endif
                </div>
            </div>
        </div>
        <hr class="border-light border-opacity-10 my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small text-white-50"><span>© {{ date('Y') }} {{ $site['company_name'] }}</span><span>Информация на сайте не является медицинской рекомендацией.</span></div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(()=>{}));}</script>
@stack('scripts')
</body>
</html>
