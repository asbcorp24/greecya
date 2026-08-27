@extends('admin.layout')

@section('title', 'Новый клиент')
@section('heading', 'Новый клиент')
@section('eyebrow', 'Клиентская база · CRM 360°')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Создание клиента</h2>
        <div class="text-muted">Карточка клиента → QR-доступ → продажа → посещения и история.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.customers.index') }}">
        <i class="bi bi-arrow-left me-1"></i>К клиентской базе
    </a>
</div>

<form method="post" action="{{ route('admin.customers.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="admin-card p-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge text-bg-primary">1</span>
                    <h3 class="h5 mb-0">Основные данные</h3>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Фамилия *</label>
                        <input class="form-control" name="last_name" value="{{ old('last_name') }}" maxlength="80" required autofocus>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Имя *</label>
                        <input class="form-control" name="first_name" value="{{ old('first_name') }}" maxlength="80" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Отчество</label>
                        <input class="form-control" name="patronymic" value="{{ old('patronymic') }}" maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Телефон *</label>
                        <input class="form-control" name="phone" value="{{ old('phone') }}" maxlength="40" required placeholder="+7 999 000-00-00">
                        <div class="form-text">Используется для поиска дублей и идентификации клиента.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" maxlength="190" placeholder="client@example.ru">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Дата рождения</label>
                        <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date') }}" max="{{ today()->toDateString() }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Пол</label>
                        <select class="form-select" name="gender">
                            <option value="">Не указан</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Женский</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Мужской</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Источник *</label>
                        <select class="form-select" name="source" required>
                            @foreach($sources as $value => $label)
                                <option value="{{ $value }}" {{ old('source', 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Экстренный контакт</label>
                        <input class="form-control" name="emergency_contact" value="{{ old('emergency_contact') }}" maxlength="120" placeholder="ФИО и телефон родственника / представителя">
                    </div>
                </div>
            </div>

            <div class="admin-card p-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge text-bg-primary">2</span>
                    <h3 class="h5 mb-0">Фото и служебная информация</h3>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Фото клиента</label>
                        <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">JPEG, PNG или WebP, до 5 МБ. Фото попадёт в карточку 360° и на печатную QR-карту.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Заметка</label>
                        <textarea class="form-control" name="notes" rows="4" maxlength="5000" placeholder="Особенности обслуживания, пожелания клиента и т. п.">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge text-bg-primary">3</span>
                    <h3 class="h5 mb-0">Согласия</h3>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="privacyConsent" name="privacy_consent" {{ old('privacy_consent') ? 'checked' : '' }} required>
                    <label class="form-check-label" for="privacyConsent">
                        Клиент дал согласие на обработку персональных данных *
                    </label>
                    <div class="form-text">Дата и время согласия будут сохранены в карточке клиента.</div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="marketingConsent" name="marketing_consent" {{ old('marketing_consent') ? 'checked' : '' }}>
                    <label class="form-check-label" for="marketingConsent">
                        Клиент согласен получать информационные и рекламные сообщения
                    </label>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="admin-card p-4 mb-4 position-sticky" style="top:24px">
                <h3 class="h5">После создания</h3>

                <div class="form-check form-switch my-4">
                    <input class="form-check-input" type="checkbox" role="switch" value="1" id="issueQr" name="issue_qr" {{ old('issue_qr', '1') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="issueQr">Сразу выдать QR-карту</label>
                    <div class="form-text">QR будет использоваться существующим СКУД и сможет быть распечатан на карте клиента.</div>
                </div>

                <div class="border rounded-4 p-3 bg-light mb-4">
                    <div class="d-flex gap-3 mb-3"><i class="bi bi-person-check fs-4"></i><div><strong>1. Клиент</strong><div class="small text-muted">Создаётся карточка CRM 360°.</div></div></div>
                    <div class="d-flex gap-3 mb-3"><i class="bi bi-qr-code fs-4"></i><div><strong>2. QR</strong><div class="small text-muted">При включённой опции выдаётся карта доступа.</div></div></div>
                    <div class="d-flex gap-3"><i class="bi bi-arrow-right-circle fs-4"></i><div><strong>3. Карточка 360°</strong><div class="small text-muted">После сохранения откроется созданный клиент.</div></div></div>
                </div>

                <button class="btn btn-primary btn-lg w-100" type="submit">
                    <i class="bi bi-person-plus me-1"></i>Создать клиента
                </button>
                <a class="btn btn-link w-100 mt-2" href="{{ route('admin.customers.index') }}">Отмена</a>
            </div>
        </div>
    </div>
</form>
@endsection
