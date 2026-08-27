@extends('admin.layout')

@section('title','Редактирование услуги')
@section('heading','Редактирование услуги')
@section('eyebrow','Услуги комплекса')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">{{ $service->name }}</h2>
        <div class="text-muted">Здесь редактируются данные отдельной публичной страницы услуги и её фотографии.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.services.index') }}"><i class="bi bi-arrow-left me-1"></i>К списку</a>
        @if($service->is_active)
            <a class="btn btn-outline-success" href="{{ route('services.show', ['service' => $service->slug]) }}" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Страница услуги</a>
        @endif
        @if(auth()->user()->hasPermission('bookings.manage'))
            <a class="btn btn-outline-primary" href="{{ route('admin.schedule.index') }}"><i class="bi bi-calendar3 me-1"></i>Расписание</a>
        @endif
    </div>
</div>

<form method="post" action="{{ route('admin.services.update', $service) }}" enctype="multipart/form-data">
    @csrf
    @method('patch')
    @include('admin.services._form')
</form>

<div class="admin-card p-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h3 class="mb-1">Дополнительная фотогалерея</h3>
            <div class="text-muted">Настройте подписи, порядок и видимость фотографий. Главное фото настраивается выше.</div>
        </div>
        <form method="post" action="{{ route('admin.services.photos.store', $service) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
            @csrf
            <div>
                <label class="form-label small mb-1">Добавить фотографии</label>
                <input class="form-control form-control-sm" type="file" name="images[]" accept="image/*" multiple required>
            </div>
            <button class="btn btn-sm btn-primary"><i class="bi bi-images me-1"></i>Загрузить</button>
        </form>
    </div>

    @if($service->photos->isEmpty())
        <div class="text-center text-muted py-5 border rounded-4">
            <i class="bi bi-images fs-1 d-block mb-2"></i>
            Дополнительных фотографий пока нет.
        </div>
    @else
        <div class="row g-4">
            @foreach($service->photos as $photo)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-4 overflow-hidden h-100 bg-white">
                        <img src="{{ Storage::url($photo->image_path) }}" alt="{{ $photo->caption ?: $service->name }}" class="w-100" style="height:230px;object-fit:cover">
                        <div class="p-3">
                            <form method="post" action="{{ route('admin.services.photos.update', [$service, $photo]) }}" class="row g-2">
                                @csrf
                                @method('patch')
                                <div class="col-12">
                                    <label class="form-label small">Подпись</label>
                                    <input class="form-control form-control-sm" name="caption" maxlength="500" value="{{ $photo->caption }}" placeholder="Например: Зона бассейна во время занятия">
                                </div>
                                <div class="col-5">
                                    <label class="form-label small">Порядок</label>
                                    <input type="number" class="form-control form-control-sm" name="sort_order" min="0" max="10000" value="{{ $photo->sort_order }}" required>
                                </div>
                                <div class="col-7 d-flex align-items-end">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="photo_active_{{ $photo->id }}" {{ $photo->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="photo_active_{{ $photo->id }}">Показывать</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-outline-primary w-100">Сохранить фото</button>
                                </div>
                            </form>
                            <form method="post" action="{{ route('admin.services.photos.destroy', [$service, $photo]) }}" class="mt-2">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Удалить эту фотографию без возможности восстановления?')"><i class="bi bi-trash me-1"></i>Удалить фото</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
