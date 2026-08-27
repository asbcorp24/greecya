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

<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card p-4">
            <h3 class="mb-3">Основные данные</h3>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Название *</label>
                    <input class="form-control" name="name" value="{{ old('name', $service->name) }}" required maxlength="190" placeholder="Например: Свободное плавание">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Категория *</label>
                    <select class="form-select" name="category" required>
                        @foreach($categoryLabels as $value => $label)
                            <option value="{{ $value }}" {{ old('category', $service->category ?: 'pool') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Описание</label>
                    <textarea class="form-control" name="description" rows="4" maxlength="5000" placeholder="Что входит в услугу, для кого она предназначена, особенности посещения">{{ old('description', $service->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input class="form-control" name="slug" value="{{ old('slug', $service->slug) }}" maxlength="190" placeholder="Оставьте пустым для автогенерации">
                    <div class="form-text">Только латинские буквы, цифры и дефисы. После публикации лучше не менять.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Цена, ₽ *</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $service->price ?? 0) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Минут *</label>
                    <input type="number" min="5" max="1440" class="form-control" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes ?? 60) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Мест *</label>
                    <input type="number" min="1" max="500" class="form-control" name="capacity" value="{{ old('capacity', $service->capacity ?? 1) }}" required>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 mb-4">
            <h3 class="mb-3">Доступность</h3>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $service->exists ? $service->is_active : true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active"><strong>Услуга активна</strong></label>
                <div class="small text-muted">Неактивная услуга не предлагается в новых операциях.</div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="online_booking" name="online_booking" value="1" {{ old('online_booking', $service->exists ? $service->online_booking : true) ? 'checked' : '' }}>
                <label class="form-check-label" for="online_booking"><strong>Онлайн-запись</strong></label>
                <div class="small text-muted">Показывать услугу посетителям на странице записи сайта.</div>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="requires_trainer" name="requires_trainer" value="1" {{ old('requires_trainer', $service->requires_trainer) ? 'checked' : '' }}>
                <label class="form-check-label" for="requires_trainer"><strong>Требуется тренер</strong></label>
                <div class="small text-muted">Используйте для персональных и групповых тренировок.</div>
            </div>
        </div>

        <div class="admin-card p-4">
            <label class="form-label">Порядок сортировки *</label>
            <input type="number" min="0" max="10000" class="form-control" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 100) }}" required>
            <div class="form-text">Чем меньше число, тем выше услуга в списках.</div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button class="btn btn-primary btn-lg px-4"><i class="bi bi-check2-circle me-1"></i>{{ $service->exists ? 'Сохранить изменения' : 'Создать услугу' }}</button>
    <a class="btn btn-outline-secondary btn-lg" href="{{ route('admin.services.index') }}">Отмена</a>
</div>
