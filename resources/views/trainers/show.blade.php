@extends('layouts.app')

@section('title', $trainer->name)
@section('seo_title', $trainer->name.' — тренер '.$site['site_short_name'])
@section('seo_description', Str::limit(strip_tags($trainer->bio ?: ($trainer->specialization ?: 'Тренер комплекса '.$site['site_short_name'])), 155))
@section('seo_image', $trainer->photo_path ?: optional($trainer->photos->first())->image_path)

@push('styles')
<link href="{{ asset('css/trainers.css') }}?v={{ file_exists(public_path('css/trainers.css')) ? filemtime(public_path('css/trainers.css')) : 1 }}" rel="stylesheet">
@endpush

@section('content')
<section class="trainer-profile-hero">
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Главная</a></li>
                <li class="breadcrumb-item"><a href="{{ route('home') }}#trainers">Тренеры</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $trainer->name }}</li>
            </ol>
        </nav>

        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="trainer-profile-photo">
                    @if($trainer->photo_path)
                        <img src="{{ Storage::url($trainer->photo_path) }}" alt="{{ $trainer->name }}">
                    @else
                        <div class="trainer-profile-placeholder"><i class="bi bi-person"></i></div>
                    @endif
                </div>
            </div>
            <div class="col-lg-7">
                <div class="eyebrow eyebrow-blue">Наша команда</div>
                @if($trainer->specialization)
                    <span class="trainer-profile-specialization">{{ $trainer->specialization }}</span>
                @endif
                <h1>{{ $trainer->name }}</h1>

                @if($trainer->experience_years)
                    <div class="trainer-profile-experience">
                        <i class="bi bi-award"></i>
                        <span>Стаж {{ $trainer->experience_years }} {{ trans_choice('год|года|лет', $trainer->experience_years) }}</span>
                    </div>
                @endif

                <p class="trainer-profile-intro">
                    {{ Str::limit($trainer->bio ?: 'Подробная информация о тренере скоро будет добавлена.', 320) }}
                </p>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="{{ route('booking.index') }}" class="btn btn-primary btn-lg rounded-pill px-4">Записаться</a>
                    <a href="{{ route('home') }}#trainers" class="btn btn-outline-primary btn-lg rounded-pill px-4">Все тренеры</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding trainer-profile-content">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="eyebrow eyebrow-blue">О тренере</div>
                <h2 class="section-title">Знакомьтесь ближе</h2>

                @if($trainer->bio)
                    <div class="trainer-profile-bio">{!! nl2br(e($trainer->bio)) !!}</div>
                @else
                    <p class="text-muted">Подробная биография пока заполняется.</p>
                @endif

                @if($trainer->photos->isNotEmpty())
                    <div class="trainer-gallery-section mt-5">
                        <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
                            <div>
                                <div class="eyebrow eyebrow-blue">Фотогалерея</div>
                                <h2 class="mb-0">Фото тренера</h2>
                            </div>
                            <span class="text-muted">{{ $trainer->photos->count() }} фото</span>
                        </div>

                        <div class="trainer-photo-grid">
                            @foreach($trainer->photos as $photo)
                                <button
                                    type="button"
                                    class="trainer-photo-item {{ $loop->first ? 'featured' : '' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#trainerPhotoModal"
                                    data-image="{{ Storage::url($photo->image_path) }}"
                                    data-caption="{{ $photo->caption ?: $trainer->name }}"
                                >
                                    <img src="{{ Storage::url($photo->image_path) }}" alt="{{ $photo->caption ?: $trainer->name }}" loading="lazy">
                                    <span><i class="bi bi-arrows-fullscreen"></i></span>
                                    @if($photo->caption)<small>{{ $photo->caption }}</small>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <aside class="trainer-profile-card sticky-lg-top" style="top:110px">
                    <h3>{{ $trainer->name }}</h3>

                    @if($trainer->specialization)
                        <div class="trainer-profile-fact">
                            <span><i class="bi bi-person-badge"></i> Специализация</span>
                            <strong>{{ $trainer->specialization }}</strong>
                        </div>
                    @endif

                    @if($trainer->experience_years)
                        <div class="trainer-profile-fact">
                            <span><i class="bi bi-award"></i> Стаж</span>
                            <strong>{{ $trainer->experience_years }} лет</strong>
                        </div>
                    @endif

                    <a href="{{ route('booking.index') }}" class="btn btn-primary btn-lg rounded-pill w-100 mt-3">Выбрать время</a>
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}" class="btn btn-outline-primary rounded-pill w-100 mt-2">Позвонить</a>
                </aside>
            </div>
        </div>
    </div>
</section>

@if($trainer->photos->isNotEmpty())
<div class="modal fade trainer-photo-modal" id="trainerPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="modal-title h5" id="trainerPhotoCaption">{{ $trainer->name }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body pt-0">
                <img id="trainerPhotoLarge" src="" alt="" class="w-100">
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@if($trainer->photos->isNotEmpty())
@push('scripts')
<script>
(() => {
    const modal = document.getElementById('trainerPhotoModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const image = button?.dataset.image || '';
        const caption = button?.dataset.caption || '';

        const large = document.getElementById('trainerPhotoLarge');
        const title = document.getElementById('trainerPhotoCaption');

        large.src = image;
        large.alt = caption;
        title.textContent = caption;
    });
})();
</script>
@endpush
@endif
