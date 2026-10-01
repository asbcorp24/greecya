@extends('admin.layout')
@section('title','Тренеры')
@section('heading','Тренеры')
@section('eyebrow','Команда комплекса')

@section('content')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="admin-card p-4 sticky-xl-top" style="top:1rem">
            <h3>Добавить тренера</h3>
            <p class="text-muted">После сохранения у тренера появится отдельная публичная страница.</p>

            <form method="post" action="{{ route('admin.trainers.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">ФИО</label>
                    <input class="form-control" name="name" value="{{ old('name') }}" placeholder="ФИО" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Адрес страницы <small class="text-muted">необязательно</small></label>
                    <input class="form-control" name="slug" value="{{ old('slug') }}" placeholder="ivan-ivanov">
                </div>
                <div class="col-12">
                    <label class="form-label">Специализация</label>
                    <input class="form-control" name="specialization" value="{{ old('specialization') }}" placeholder="Тренер по плаванию">
                </div>
                <div class="col-6">
                    <label class="form-label">Телефон</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}" placeholder="Телефон">
                </div>
                <div class="col-6">
                    <label class="form-label">Стаж, лет</label>
                    <input type="number" class="form-control" name="experience_years" value="{{ old('experience_years', 0) }}" min="0" max="80">
                </div>
                <div class="col-12">
                    <label class="form-label">Биография</label>
                    <textarea class="form-control" name="bio" rows="7" placeholder="Образование, опыт, подход к тренировкам, достижения...">{{ old('bio') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Основное фото</label>
                    <input type="file" class="form-control" name="photo" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label">Фотогалерея <small class="text-muted">можно выбрать несколько</small></label>
                    <input type="file" class="form-control" name="gallery_images[]" accept="image/*" multiple>
                </div>
                <div class="col-6">
                    <label class="form-label">Сортировка</label>
                    <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', 100) }}">
                </div>
                <div class="col-6 d-flex align-items-end pb-2">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="trainerActiveNew" checked>
                        <label class="form-check-label" for="trainerActiveNew">На сайте</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary w-100">Добавить тренера</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="row g-4">
            @forelse($trainers as $trainer)
                <div class="col-12">
                    <article class="admin-card p-4">
                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-between mb-4">
                            <div class="d-flex gap-3 align-items-center">
                                @if($trainer->photo_path)
                                    <img class="trainer-admin-photo" src="{{ Storage::url($trainer->photo_path) }}" alt="{{ $trainer->name }}">
                                @else
                                    <div class="trainer-admin-photo placeholder"><i class="bi bi-person"></i></div>
                                @endif
                                <div>
                                    <h3 class="mb-1">{{ $trainer->name }}</h3>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge text-bg-light">{{ $trainer->slots_count }} слотов</span>
                                        <span class="badge text-bg-light">{{ $trainer->photos->count() }} фото</span>
                                        @unless($trainer->is_active)<span class="badge text-bg-secondary">Скрыт</span>@endunless
                                    </div>
                                </div>
                            </div>

                            @if($trainer->slug)
                                <a href="{{ route('trainers.show', ['trainer' => $trainer->slug]) }}" target="_blank" class="btn btn-outline-primary rounded-pill align-self-start">
                                    Страница тренера <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            @endif
                        </div>

                        <form method="post" action="{{ route('admin.trainers.update',$trainer) }}" enctype="multipart/form-data" class="row g-3">
                            @csrf
                            @method('patch')
                            <div class="col-md-7">
                                <label class="form-label">ФИО</label>
                                <input class="form-control" name="name" value="{{ $trainer->name }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Адрес страницы</label>
                                <input class="form-control" name="slug" value="{{ $trainer->slug }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Специализация</label>
                                <input class="form-control" name="specialization" value="{{ $trainer->specialization }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Стаж</label>
                                <input type="number" class="form-control" name="experience_years" value="{{ $trainer->experience_years }}" min="0" max="80">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Порядок</label>
                                <input type="number" class="form-control" name="sort_order" value="{{ $trainer->sort_order }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Телефон</label>
                                <input class="form-control" name="phone" value="{{ $trainer->phone }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Биография</label>
                                <textarea class="form-control" name="bio" rows="7">{{ $trainer->bio }}</textarea>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Заменить основное фото</label>
                                <input type="file" class="form-control" name="photo" accept="image/*">
                            </div>
                            <div class="col-md-5 d-flex align-items-end gap-3 pb-2">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeTrainer{{ $trainer->id }}" @checked($trainer->is_active)>
                                    <label class="form-check-label" for="activeTrainer{{ $trainer->id }}">Показывать на сайте</label>
                                </div>
                                <button class="btn btn-dark btn-sm">Сохранить</button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-end mb-3">
                            <div>
                                <h4 class="mb-1">Фотогалерея тренера</h4>
                                <p class="text-muted mb-0">Фото появятся на отдельной странице тренера.</p>
                            </div>
                            <form method="post" action="{{ route('admin.trainers.photos.store', $trainer) }}" enctype="multipart/form-data" class="d-flex flex-column flex-sm-row gap-2">
                                @csrf
                                <input type="file" class="form-control" name="gallery_images[]" accept="image/*" multiple required>
                                <button class="btn btn-primary text-nowrap">Добавить фото</button>
                            </form>
                        </div>

                        @if($trainer->photos->isNotEmpty())
                            <div class="row g-3">
                                @foreach($trainer->photos as $photo)
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="gallery-admin-item h-100">
                                            <img src="{{ Storage::url($photo->image_path) }}" alt="{{ $photo->caption }}">
                                            <form method="post" action="{{ route('admin.trainers.photos.update', [$trainer, $photo]) }}" class="p-2">
                                                @csrf
                                                @method('patch')
                                                <input class="form-control form-control-sm mb-2" name="caption" value="{{ $photo->caption }}" placeholder="Подпись">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <input type="number" class="form-control form-control-sm" name="sort_order" value="{{ $photo->sort_order }}" title="Сортировка">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($photo->is_active) title="Показывать">
                                                    </div>
                                                    <button class="btn btn-dark btn-sm"><i class="bi bi-check2"></i></button>
                                                </div>
                                            </form>
                                            <form method="post" action="{{ route('admin.trainers.photos.destroy', [$trainer, $photo]) }}" class="px-2 pb-2" onsubmit="return confirm('Удалить фотографию?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-link text-danger p-0">Удалить фото</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-3 bg-light p-4 text-center text-muted">Фотографий пока нет.</div>
                        @endif

                        <form method="post" action="{{ route('admin.trainers.destroy',$trainer) }}" class="mt-4" onsubmit="return confirm('Удалить тренера?')">
                            @csrf
                            @method('delete')
                            <button class="btn btn-outline-danger btn-sm">Удалить тренера</button>
                        </form>
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="admin-card p-5 text-center text-muted">Тренеров пока нет.</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
