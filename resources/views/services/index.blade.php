@extends('layouts.app')

@section('title','Услуги комплекса')
@section('seo_title','Услуги комплекса — '.$site['site_short_name'])
@section('seo_description','Бассейн, тренировки, SPA, массаж, сауна и другие услуги комплекса '.$site['site_short_name'].'. Цены, длительность, фотографии и онлайн-запись.')

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
@endphp

<section class="page-hero compact service-catalog-hero">
    <div class="container py-5">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <div class="eyebrow"><i class="bi bi-stars"></i> {{ $site['site_short_name'] }}</div>
                <h1 class="display-3 mb-3">Услуги комплекса</h1>
                <p class="lead mb-0">Подробная информация, фотографии, стоимость, длительность и доступная запись по каждой услуге.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('booking.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Перейти к расписанию</a>
            </div>
        </div>
    </div>
</section>

<section class="section-padding service-catalog-page">
    <div class="container">
        @if($categories->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mb-5">
                <a class="btn rounded-pill {{ request('category') ? 'btn-outline-primary' : 'btn-primary' }}" href="{{ route('services.index') }}">Все услуги</a>
                @foreach($categories as $category)
                    <a class="btn rounded-pill {{ request('category') === $category ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('services.index', ['category' => $category]) }}">{{ $categoryLabels[$category] ?? $category }}</a>
                @endforeach
            </div>
        @endif

        <div class="row g-4">
            @forelse($services as $service)
                @php($cover = $service->main_image_path ?: optional($service->photos->first())->image_path)
                <div class="col-md-6 col-xl-4">
                    <article class="public-service-card h-100">
                        <a href="{{ route('services.show', ['service' => $service->slug]) }}" class="public-service-cover">
                            @if($cover)
                                <img src="{{ Storage::url($cover) }}" alt="{{ $service->name }}">
                            @else
                                <div class="public-service-placeholder"><i class="bi bi-water"></i></div>
                            @endif
                            <span class="public-service-category">{{ $categoryLabels[$service->category] ?? $service->category }}</span>
                        </a>
                        <div class="public-service-body">
                            <div class="d-flex gap-3 small text-muted mb-2">
                                <span><i class="bi bi-clock me-1"></i>{{ $service->duration_minutes }} мин</span>
                                <span><i class="bi bi-people me-1"></i>до {{ $service->capacity }} чел.</span>
                            </div>
                            <h2><a href="{{ route('services.show', ['service' => $service->slug]) }}">{{ $service->name }}</a></h2>
                            <p>{{ Str::limit($service->description ?: 'Подробная информация об услуге доступна на отдельной странице.', 180) }}</p>
                            <div class="public-service-bottom">
                                <strong>{{ number_format((float)$service->price, 0, ',', ' ') }} ₽</strong>
                                <a class="btn btn-outline-primary rounded-pill" href="{{ route('services.show', ['service' => $service->slug]) }}">Подробнее <i class="bi bi-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-stars"></i>
                        <h2>Услуги пока не опубликованы</h2>
                        <p class="text-muted mb-0">Попробуйте выбрать другую категорию или вернуться позже.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($services->hasPages())
            <div class="mt-5">{{ $services->links() }}</div>
        @endif
    </div>
</section>
@endsection
