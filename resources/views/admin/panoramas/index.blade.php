@extends('admin.layout')
@section('title', '360° панорамы')
@section('heading', '360° панорамы')
@section('eyebrow', 'Контент сайта')

@section('content')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="admin-card p-4 sticky-xl-top" style="top:1rem">
            <div class="d-flex align-items-start gap-3 mb-3">
                <span class="fs-3 text-primary"><i class="bi bi-badge-3d"></i></span>
                <div>
                    <h3 class="mb-1">Новая панорама</h3>
                    <p class="text-muted mb-0">Загружайте equirectangular 360° фото. Рекомендуемое соотношение сторон 2:1, например 8000×4000.</p>
                </div>
            </div>

            <form method="post" action="{{ route('admin.panoramas.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Название</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" placeholder="Например: Бассейн" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Описание</label>
                    <textarea class="form-control" name="description" rows="4" placeholder="Короткое описание точки обзора">{{ old('description') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">360° фото</label>
                    <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                    <small class="text-muted">JPG, PNG или WebP, до 30 МБ.</small>
                </div>
                <div class="col-5">
                    <label class="form-label">Сортировка</label>
                    <input type="number" class="form-control" name="sort_order" min="0" max="9999" value="{{ old('sort_order', 100) }}">
                </div>
                <div class="col-7 d-flex flex-column justify-content-end gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newPanoramaActive" checked>
                        <label class="form-check-label" for="newPanoramaActive">Показывать на сайте</label>
                    </div>
                    <input type="hidden" name="is_homepage" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_homepage" value="1" id="newPanoramaHomepage">
                        <label class="form-check-label" for="newPanoramaHomepage">Главная панорама</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary w-100"><i class="bi bi-cloud-arrow-up me-1"></i> Загрузить панораму</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h3 class="mb-1">Загруженные панорамы</h3>
                <p class="text-muted mb-0">Главная панорама показывается первой в 360° блоке сайта.</p>
            </div>
            <a href="{{ route('home') }}#panoramas" target="_blank" class="btn btn-outline-primary rounded-pill">
                Открыть на сайте <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @forelse($panoramas as $panorama)
                <div class="col-lg-6">
                    <article class="admin-card overflow-hidden h-100">
                        <div class="position-relative">
                            <img src="{{ Storage::url($panorama->image_path) }}" alt="{{ $panorama->title }}" class="w-100" style="height:210px;object-fit:cover">
                            <div class="position-absolute top-0 start-0 p-3 d-flex gap-2 flex-wrap">
                                @if($panorama->is_homepage)
                                    <span class="badge text-bg-primary"><i class="bi bi-house-fill me-1"></i> Главная</span>
                                @endif
                                @unless($panorama->is_active)
                                    <span class="badge text-bg-secondary">Скрыта</span>
                                @endunless
                                <span class="badge text-bg-dark">360°</span>
                            </div>
                        </div>

                        <form method="post" action="{{ route('admin.panoramas.update', $panorama) }}" enctype="multipart/form-data" class="p-3 row g-2">
                            @csrf
                            @method('patch')
                            <div class="col-12">
                                <label class="form-label small">Название</label>
                                <input class="form-control" name="title" value="{{ $panorama->title }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Описание</label>
                                <textarea class="form-control" name="description" rows="3">{{ $panorama->description }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Заменить 360° фото</label>
                                <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Порядок</label>
                                <input type="number" class="form-control" name="sort_order" min="0" max="9999" value="{{ $panorama->sort_order }}">
                            </div>
                            <div class="col-8 d-flex flex-column justify-content-end gap-2 pb-1">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $panorama->id }}" @checked($panorama->is_active)>
                                    <label class="form-check-label" for="active{{ $panorama->id }}">На сайте</label>
                                </div>
                                <input type="hidden" name="is_homepage" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_homepage" value="1" id="homepage{{ $panorama->id }}" @checked($panorama->is_homepage)>
                                    <label class="form-check-label" for="homepage{{ $panorama->id }}">Главная</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-dark w-100"><i class="bi bi-check2 me-1"></i> Сохранить</button>
                            </div>
                        </form>

                        <form method="post" action="{{ route('admin.panoramas.destroy', $panorama) }}" class="px-3 pb-3" onsubmit="return confirm('Удалить эту 360° панораму?')">
                            @csrf
                            @method('delete')
                            <button class="btn btn-link text-danger p-0"><i class="bi bi-trash me-1"></i>Удалить</button>
                        </form>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="admin-card p-5 text-center text-muted">
                        <i class="bi bi-badge-3d fs-1 d-block mb-3"></i>
                        Панорам пока нет. Загрузите первое 360° фото.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
