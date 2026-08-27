@extends('admin.layout')

@section('title','Услуги комплекса')
@section('heading','Услуги комплекса')
@section('eyebrow','Каталог услуг и онлайн-запись')

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

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Каталог услуг</h2>
        <div class="text-muted">Здесь настраиваются услуги, которые используются в расписании и онлайн-записи. Билеты и абонементы настраиваются отдельно в «Товары и тарифы».</div>
    </div>
    @if(auth()->user()->hasPermission('services.manage'))
        <a class="btn btn-primary btn-lg" href="{{ route('admin.services.create') }}"><i class="bi bi-plus-lg me-1"></i>Новая услуга</a>
    @endif
</div>

<div class="admin-card p-4 mb-4">
    <form class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Поиск</label>
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Название, slug или описание">
        </div>
        <div class="col-md-3">
            <label class="form-label">Категория</label>
            <select class="form-select" name="category">
                <option value="">Все категории</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $categoryLabels[$category] ?? $category }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Статус</label>
            <select class="form-select" name="status">
                <option value="">Все</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Активные</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Отключённые</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary flex-grow-1">Найти</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.services.index') }}"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
            <tr>
                <th>Услуга</th>
                <th>Категория</th>
                <th>Параметры</th>
                <th>Использование</th>
                <th>Доступность</th>
                <th class="text-end">Действия</th>
            </tr>
            </thead>
            <tbody>
            @forelse($services as $service)
                <tr>
                    <td style="min-width:260px">
                        <strong>{{ $service->name }}</strong>
                        <small class="d-block text-muted">{{ $service->slug }}</small>
                        @if($service->description)
                            <small class="d-block mt-1">{{ \Illuminate\Support\Str::limit($service->description, 90) }}</small>
                        @endif
                    </td>
                    <td><span class="badge text-bg-light border">{{ $categoryLabels[$service->category] ?? $service->category }}</span></td>
                    <td>
                        <div><strong>{{ number_format((float)$service->price, 0, ',', ' ') }} ₽</strong></div>
                        <small>{{ $service->duration_minutes }} мин · {{ $service->capacity }} мест</small>
                        @if($service->requires_trainer)<small class="d-block text-primary">нужен тренер</small>@endif
                    </td>
                    <td>
                        <small class="d-block">Слотов: <strong>{{ $service->slots_count }}</strong></small>
                        <small class="d-block">Записей: <strong>{{ $service->bookings_count }}</strong></small>
                    </td>
                    <td>
                        <span class="badge {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Активна' : 'Отключена' }}</span>
                        <small class="d-block mt-1 {{ $service->online_booking ? 'text-success' : 'text-muted' }}">{{ $service->online_booking ? 'Онлайн-запись включена' : 'Онлайн-запись выключена' }}</small>
                    </td>
                    <td class="text-end" style="min-width:210px">
                        @if(auth()->user()->hasPermission('services.manage'))
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.services.edit', $service) }}">Изменить</a>
                            <form method="post" action="{{ route('admin.services.toggle', $service) }}" class="d-inline">
                                @csrf
                                @method('patch')
                                <button class="btn btn-sm {{ $service->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" onclick="return confirm('{{ $service->is_active ? 'Отключить услугу? Новые записи на неё будут недоступны.' : 'Включить услугу?' }}')">
                                    {{ $service->is_active ? 'Отключить' : 'Включить' }}
                                </button>
                            </form>
                        @else
                            <span class="text-muted small">только просмотр</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">Услуги не найдены.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $services->links() }}</div>
</div>

<div class="alert alert-light border rounded-4 mt-4 mb-0">
    <strong>Важно:</strong> услуги не удаляются физически из CRM, потому что к ним могут быть привязаны расписание и история записей. Если услуга больше не используется, нажмите «Отключить».
</div>
@endsection
