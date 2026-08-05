@extends('layouts.app')

@section('content')
<section class="hero-section">
    <div class="hero-orb hero-orb-one"></div><div class="hero-orb hero-orb-two"></div>
    <div class="container position-relative">
        <div class="row align-items-center g-5 min-vh-75">
            <div class="col-lg-7 py-5">
                <div class="eyebrow"><i class="bi bi-water"></i> Васильево · Зеленодольский район</div>
                <h1>Ваше море рядом.<br><span>Бассейн и SPA</span> для всей семьи</h1>
                <p class="hero-lead">25-метровый бассейн с морской водой температурой 32°C, профессиональные тренеры и пространство для полноценного восстановления.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 mt-4">
                    <a href="{{ route('booking.index') }}" class="btn btn-primary btn-lg rounded-pill px-4">Подобрать время <i class="bi bi-arrow-right ms-2"></i></a>
                    <a href="{{ route('catalog.index') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">Купить абонемент</a>
                </div>
                <div class="hero-trust mt-5">
                    <div><strong>25 м</strong><span>длина бассейна</span></div>
                    <div><strong>32°C</strong><span>температура воды</span></div>
                    <div><strong>7 дней</strong><span>в неделю</span></div>
                </div>
            </div>
            <div class="col-lg-5 pb-5 py-lg-5">
                <div class="hero-card">
                    <div class="hero-card-wave"></div>
                    <span class="hero-card-icon"><i class="bi bi-calendar2-check"></i></span>
                    <h3>Найдём удобное время</h3>
                    <p>Свободное плавание, персональная тренировка или SPA-процедура — выберите формат и доступный слот онлайн.</p>
                    <a href="{{ route('booking.index') }}" class="btn btn-light w-100 rounded-pill py-3 fw-bold">Посмотреть расписание</a>
                    <div class="d-flex align-items-center gap-2 mt-4 small"><span class="online-dot"></span> Актуальные свободные места</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding intro-section">
    <div class="container">
        <div class="row g-4 align-items-end mb-5">
            <div class="col-lg-7"><div class="eyebrow eyebrow-blue">Комплекс заботы о себе</div><h2 class="section-title">Движение, отдых и восстановление <span>в одном месте</span></h2></div>
            <div class="col-lg-5"><p class="section-text mb-0">Приходите всей семьёй: научиться плавать, поддерживать форму, расслабиться после рабочей недели или пройти курс SPA-процедур.</p></div>
        </div>
        <div class="row g-4 feature-row">
            <div class="col-md-4"><div class="feature-card"><span><i class="bi bi-droplet"></i></span><h4>Морская вода</h4><p>Комфортная температура 32°C и мягкая атмосфера для занятий и отдыха.</p></div></div>
            <div class="col-md-4"><div class="feature-card"><span><i class="bi bi-person-arms-up"></i></span><h4>Тренеры рядом</h4><p>Обучение плаванию взрослых и детей, индивидуальные и групповые занятия.</p></div></div>
            <div class="col-md-4"><div class="feature-card"><span><i class="bi bi-flower1"></i></span><h4>SPA-восстановление</h4><p>Массаж, прессотерапия, душ Шарко, обёртывания, сауна и соляная комната.</p></div></div>
        </div>
    </div>
</section>

<section class="section-padding services-section" id="services">
    <div class="container">
        <div class="text-center mx-auto section-heading"><div class="eyebrow eyebrow-blue justify-content-center">Выберите свой формат</div><h2 class="section-title">Услуги комплекса</h2><p class="section-text">Цены в демонстрационной версии редактируются администратором в CRM.</p></div>
        <div class="row g-4 mt-3">
            @php($icons = ['pool' => 'bi-water', 'training' => 'bi-stopwatch', 'spa' => 'bi-flower2', 'salt' => 'bi-cloud-haze2'])
            @forelse($services as $service)
                <div class="col-md-6 col-xl-4">
                    <div class="service-card h-100">
                        <div class="service-icon"><i class="bi {{ $icons[$service->category] ?? 'bi-stars' }}"></i></div>
                        <div class="service-meta"><span>{{ $service->duration_minutes }} мин</span><span>до {{ $service->capacity }} чел.</span></div>
                        <h3>{{ $service->name }}</h3>
                        <p>{{ $service->description }}</p>
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-3">
                            <strong class="service-price">от {{ number_format($service->price, 0, ',', ' ') }} ₽</strong>
                            <a href="{{ route('booking.index', ['service' => $service->id]) }}" class="circle-link" aria-label="Записаться"><i class="bi bi-arrow-up-right"></i></a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-light">После запуска выполните <code>php artisan migrate --seed</code>, чтобы загрузить услуги.</div></div>
            @endforelse
        </div>
    </div>
</section>

<section class="section-padding steps-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5"><div class="eyebrow">Запись без звонков</div><h2 class="section-title text-white">Три шага до вашего отдыха</h2><p class="text-white-50 fs-5">Система показывает только доступные места. Администратор видит заявку в CRM и подтверждает её.</p><a href="{{ route('booking.index') }}" class="btn btn-light btn-lg rounded-pill px-4 mt-3">Выбрать время</a></div>
            <div class="col-lg-7">
                <div class="step-item"><span>01</span><div><h4>Выберите услугу</h4><p>Свободное плавание, тренировка или SPA.</p></div></div>
                <div class="step-item"><span>02</span><div><h4>Найдите свободный слот</h4><p>Укажите дату и выберите удобное время.</p></div></div>
                <div class="step-item"><span>03</span><div><h4>Получите подтверждение</h4><p>Мы проверим запись и свяжемся при необходимости.</p></div></div>
            </div>
        </div>
    </div>
</section>

@if($products->isNotEmpty())
<section class="section-padding products-preview">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-5"><div><div class="eyebrow eyebrow-blue">Посещения и подарки</div><h2 class="section-title mb-0">Билеты и абонементы</h2></div><a href="{{ route('catalog.index') }}" class="btn btn-outline-primary rounded-pill px-4">Смотреть все</a></div>
        <div class="row g-4">
            @foreach($products as $product)
                <div class="col-md-4"><div class="ticket-card h-100"><span class="ticket-type">{{ $product->type === 'subscription' ? 'Абонемент' : ($product->type === 'gift' ? 'Подарок' : 'Билет') }}</span><h3>{{ $product->name }}</h3><p>{{ $product->description }}</p><div class="ticket-bottom"><strong>{{ number_format($product->price, 0, ',', ' ') }} ₽</strong><a href="{{ route('catalog.index') }}" class="circle-link"><i class="bi bi-arrow-right"></i></a></div></div></div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="gift-section">
    <div class="container">
        <div class="gift-card">
            <div class="gift-decoration">Γ</div>
            <div class="row align-items-center g-4 position-relative">
                <div class="col-lg-8"><div class="eyebrow">Подарок новым подписчикам</div><h2>Напишите слово <span>«БАССЕЙН»</span></h2><p>Подпишитесь на <strong>{{ config('app.complex.social') }}</strong>, отправьте кодовое слово и получите сеанс соляной комнаты в подарок.</p></div>
                <div class="col-lg-4 text-lg-end"><a href="#callback" class="btn btn-light btn-lg rounded-pill px-4">Узнать подробнее</a></div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding callback-section" id="callback">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5"><div class="eyebrow eyebrow-blue">Нужна помощь?</div><h2 class="section-title">Подберём формат и удобное время</h2><p class="section-text">Оставьте номер телефона. Администратор поможет выбрать свободное плавание, тренировку или процедуру.</p><div class="contact-pill"><i class="bi bi-telephone"></i><div><small>Позвонить сейчас</small><a href="tel:+79655877799">{{ config('app.complex.phone') }}</a></div></div></div>
            <div class="col-lg-7">
                <div class="callback-card">
                    @if(session('lead_success'))<div class="alert alert-success rounded-4">{{ session('lead_success') }}</div>@endif
                    <form method="post" action="{{ route('lead.store') }}" class="row g-3">@csrf
                        <div class="col-md-6"><label class="form-label">Ваше имя</label><input class="form-control form-control-lg" name="name" value="{{ old('name') }}" required></div>
                        <div class="col-md-6"><label class="form-label">Телефон</label><input class="form-control form-control-lg" name="phone" value="{{ old('phone') }}" placeholder="+7 ___ ___-__-__" required></div>
                        <div class="col-12"><label class="form-label">Что вас интересует?</label><textarea class="form-control" name="request" rows="3" placeholder="Например: свободное плавание вечером для двух человек">{{ old('request') }}</textarea></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" value="1" id="privacyLead" required><label class="form-check-label small text-muted" for="privacyLead">Согласен с <a href="{{ route('privacy') }}">политикой обработки данных</a></label></div></div>
                        <div class="col-12"><button class="btn btn-primary btn-lg rounded-pill px-5" type="submit">Перезвоните мне</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="contact-section">
    <div class="container"><div class="contact-panel"><div class="row g-4 align-items-center"><div class="col-lg-7"><span class="contact-icon"><i class="bi bi-geo-alt"></i></span><div><small>Ждём вас по адресу</small><h3>{{ config('app.complex.address') }}</h3><p class="mb-0">Перед первым посещением рекомендуем записаться онлайн или по телефону.</p></div></div><div class="col-lg-5 text-lg-end"><a href="https://yandex.ru/maps/?text={{ urlencode(config('app.complex.address')) }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-lg rounded-pill px-4">Открыть на карте <i class="bi bi-box-arrow-up-right ms-2"></i></a></div></div></div></div>
</section>
@endsection
