@extends('layouts.app')

@section('title', $service->name)
@section('seo_title', $service->name.' — '.$site['site_short_name'])
@section('seo_description', Str::limit(strip_tags($service->description ?: 'Подробная информация об услуге '.$service->name), 155))
@section('seo_image', $service->main_image_path ?: optional($service->photos->first())->image_path)

@section('content')
@php
    $categoryLabels = [
        'pool' => 'Бассейн',
        'training' => 'Тренировки',
        'spa' => 'SPA',
        'salt' => 'Соляная комната',
        'massage' => 'Массаж',
        'sauna' => 'Сауна',
        'other' => 'Другое',
    ];
    $cover = $service->main_image_path ?: optional($service->photos->first())->image_path;
@endphp

<section class="service-detail-hero {{ $cover ? 'has-cover' : '' }}" @if($cover) style="background-image:linear-gradient(90deg,rgba(2,31,43,.9),rgba(2,31,43,.55) 58%,rgba(2,31,43,.2)),url('{{ Storage::url($cover) }}')" @endif>
    <div class="container py-5 position-relative">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb service-breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Главная</a></li>
                <li class="breadcrumb-item"><a href="{{ route('services.index') }}">Услуги</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $service->name }}</li>
            </ol>
        </nav>
        <div class="row align-items-end g-5">
            <div class="col-lg-8">
                <span class="service-detail-category">{{ $categoryLabels[$service->category] ?? $service->category }}</span>
                <h1>{{ $service->name }}</h1>
                @if($service->description)
                    <p class="service-detail-lead">{{ Str::limit($service->description, 260) }}</p>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="service-price-panel">
                    <small>Стоимость</small>
                    <strong>{{ number_format((float)$service->price, 0, ',', ' ') }} ₽</strong>
                    @if($service->online_booking)
                        <a href="{{ route('booking.index', ['service' => $service->id]) }}" class="btn btn-primary btn-lg rounded-pill w-100">Записаться онлайн</a>
                    @else
                        <a href="{{ route('home') }}#callback" class="btn btn-light btn-lg rounded-pill w-100">Уточнить по телефону</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding service-detail-page">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="service-detail-content">
                    <div class="eyebrow eyebrow-blue">Об услуге</div>
                    <h2>Всё, что нужно знать</h2>
                    @if($service->description)
                        <div class="service-description">{!! nl2br(e($service->description)) !!}</div>
                    @else
                        <p class="text-muted">Подробное описание этой услуги пока заполняется.</p>
                    @endif
                </div>

                @if($service->photos->isNotEmpty())
                    <div class="service-gallery-section mt-5">
                        <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
                            <div>
                                <div class="eyebrow eyebrow-blue">Фотографии</div>
                                <h2 class="mb-0">Как выглядит услуга</h2>
                            </div>
                            <span class="text-muted">{{ $service->photos->count() }} фото</span>
                        </div>
                        <div class="service-photo-grid">
                            @foreach($service->photos as $photo)
                                <figure class="service-photo-item {{ $loop->first ? 'featured' : '' }}">
                                    <img src="{{ Storage::url($photo->image_path) }}" alt="{{ $photo->caption ?: $service->name }}" loading="lazy">
                                    @if($photo->caption)<figcaption>{{ $photo->caption }}</figcaption>@endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($service->online_booking && $upcomingSlots->isNotEmpty())
                    <div class="service-slots mt-5">
                        <div class="eyebrow eyebrow-blue">Ближайшее время</div>
                        <h2>Доступные занятия</h2>
                        <div class="row g-3 mt-2">
                            @foreach($upcomingSlots as $slot)
                                <div class="col-md-6">
                                    <a href="{{ route('booking.index', ['service' => $service->id]) }}" class="service-slot-card">
                                        <div>
                                            <strong>{{ $slot->starts_at->translatedFormat('d F') }}</strong>
                                            <span>{{ $slot->starts_at->format('H:i') }}–{{ $slot->ends_at->format('H:i') }}</span>
                                        </div>
                                        <div class="text-end">
                                            @if($slot->trainer)<small>{{ $slot->trainer->name }}</small>@endif
                                            <span class="service-slot-places">мест: {{ max(0, $slot->capacity - $slot->booked_count) }}</span>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('booking.index', ['service' => $service->id]) }}" class="btn btn-outline-primary rounded-pill px-4 mt-4">Посмотреть всё расписание</a>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <aside class="service-facts-card sticky-lg-top" style="top:110px">
                    <h3>Основные параметры</h3>
                    <div class="service-fact">
                        <span><i class="bi bi-clock"></i> Длительность</span>
                        <strong>{{ $service->duration_minutes }} мин</strong>
                    </div>
                    <div class="service-fact">
                        <span><i class="bi bi-people"></i> Вместимость</span>
                        <strong>до {{ $service->capacity }} чел.</strong>
                    </div>
                    <div class="service-fact">
                        <span><i class="bi bi-person-badge"></i> Тренер</span>
                        <strong>{{ $service->requires_trainer ? 'Требуется' : 'Не требуется' }}</strong>
                    </div>
                    <div class="service-fact">
                        <span><i class="bi bi-calendar2-check"></i> Онлайн-запись</span>
                        <strong>{{ $service->online_booking ? 'Доступна' : 'По телефону' }}</strong>
                    </div>
                    <div class="service-fact price">
                        <span>Стоимость</span>
                        <strong>{{ number_format((float)$service->price, 0, ',', ' ') }} ₽</strong>
                    </div>
                    @if($service->online_booking)
                        <a href="{{ route('booking.index', ['service' => $service->id]) }}" class="btn btn-primary btn-lg rounded-pill w-100 mt-3">Выбрать время</a>
                    @else
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}" class="btn btn-primary btn-lg rounded-pill w-100 mt-3"><i class="bi bi-telephone me-1"></i>{{ $site['phone'] }}</a>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</section>

@if($related->isNotEmpty())
<section class="section-padding related-services-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end gap-3 mb-5">
            <div>
                <div class="eyebrow eyebrow-blue">В этой категории</div>
                <h2 class="section-title mb-0">Другие услуги</h2>
            </div>
            <a href="{{ route('services.index') }}" class="btn btn-outline-primary rounded-pill">Все услуги</a>
        </div>
        <div class="row g-4">
            @foreach($related as $item)
                <div class="col-md-4">
                    <a href="{{ route('services.show', ['service' => $item->slug]) }}" class="related-service-card">
                        <small>{{ $categoryLabels[$item->category] ?? $item->category }}</small>
                        <h3>{{ $item->name }}</h3>
                        <div><strong>{{ number_format((float)$item->price, 0, ',', ' ') }} ₽</strong><i class="bi bi-arrow-up-right"></i></div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
