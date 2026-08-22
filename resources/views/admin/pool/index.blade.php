@extends('admin.layout')

@section('title', 'Бассейн')
@section('heading', 'Бассейны и дорожки')
@section('eyebrow', 'Операционное управление')

@php
    $laneCount = $zones->sum(function ($zone) {
        return $zone->lanes->count();
    });
@endphp

@section('content')
@if($errors->has('zone'))
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('zone') }}
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="pool-kpi">
            <i class="bi bi-water"></i>
            <strong>{{ $zones->count() }}</strong>
            <small>бассейнов / зон</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="pool-kpi">
            <i class="bi bi-distribute-horizontal"></i>
            <strong>{{ $laneCount }}</strong>
            <small>дорожек</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="pool-kpi">
            <i class="bi bi-calendar-week"></i>
            <strong>{{ $slots->count() }}</strong>
            <small>сеансов на 7 дней</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="pool-kpi">
            <i class="bi bi-tools"></i>
            <strong>{{ $maintenance->whereIn('status', ['open', 'in_progress'])->count() }}</strong>
            <small>технических задач</small>
        </div>
    </div>
</div>

<ul class="nav nav-pills crm-tabs mb-4">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#lanes">Бассейны и дорожки</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#sessions">Сеансы и лист ожидания</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#water">Вода</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#maintenance">Техобслуживание</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="lanes">
        <div class="row g-4">
            <div class="col-xl-4">
                <div class="admin-card p-4 mb-4">
                    <h3>Добавить бассейн / зону</h3>
                    <p class="text-muted small">Основной бассейн, детский бассейн, SPA или другая водная зона.</p>
                    <form method="post" action="{{ route('admin.pool.zones.store') }}" class="row g-2">
                        @csrf
                        <input type="hidden" name="action" value="create">
                        <div class="col-12"><input class="form-control" name="name" placeholder="Основной бассейн" required></div>
                        <div class="col-6"><input class="form-control" name="code" placeholder="POOL25" required></div>
                        <div class="col-6">
                            <select class="form-select" name="type">
                                <option value="pool">Бассейн</option>
                                <option value="kids_pool">Детский бассейн</option>
                                <option value="spa">SPA</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>
                        <div class="col-6"><input type="number" class="form-control" name="capacity" value="30" min="1" max="1000" required></div>
                        <div class="col-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">Активен</label>
                            </div>
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100">Добавить бассейн</button></div>
                    </form>
                </div>

                <div class="admin-card p-4">
                    <h3>Добавить дорожку</h3>
                    <form method="post" action="{{ route('admin.pool.lanes.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <select class="form-select" name="pool_zone_id" required>
                                <option value="">Выберите бассейн</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-8"><input class="form-control" name="name" placeholder="Дорожка 1" required></div>
                        <div class="col-4"><input type="number" class="form-control" name="number" placeholder="№" required></div>
                        <div class="col-6"><input type="number" step="0.1" class="form-control" name="length_meters" value="25" required></div>
                        <div class="col-6"><input type="number" class="form-control" name="capacity" value="6" required></div>
                        <div class="col-12"><button class="btn btn-dark w-100">Добавить дорожку</button></div>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                @forelse($zones as $zone)
                    <div class="admin-card p-4 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="mb-1">{{ $zone->name }}</h3>
                                <p class="text-muted mb-0">{{ $zone->code }} · вместимость {{ $zone->capacity }}</p>
                            </div>
                            <span class="badge {{ $zone->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $zone->is_active ? 'работает' : 'выключен' }}
                            </span>
                        </div>

                        <details class="border rounded-3 p-3 mb-4">
                            <summary class="fw-semibold"><i class="bi bi-pencil-square me-2"></i>Редактировать бассейн / зону</summary>
                            <form method="post" action="{{ route('admin.pool.zones.store') }}" class="row g-2 mt-3">
                                @csrf
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="zone_id" value="{{ $zone->id }}">

                                <div class="col-md-5">
                                    <label class="form-label">Название</label>
                                    <input class="form-control" name="name" value="{{ $zone->name }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Код</label>
                                    <input class="form-control" name="code" value="{{ $zone->code }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Тип</label>
                                    <select class="form-select" name="type">
                                        <option value="pool" {{ $zone->type === 'pool' ? 'selected' : '' }}>Бассейн</option>
                                        <option value="kids_pool" {{ $zone->type === 'kids_pool' ? 'selected' : '' }}>Детский бассейн</option>
                                        <option value="spa" {{ $zone->type === 'spa' ? 'selected' : '' }}>SPA</option>
                                        <option value="other" {{ $zone->type === 'other' ? 'selected' : '' }}>Другое</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Вместимость</label>
                                    <input type="number" class="form-control" name="capacity" value="{{ $zone->capacity }}" min="1" max="1000" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <label class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $zone->is_active ? 'checked' : '' }}>
                                        <span class="form-check-label">Бассейн активен</span>
                                    </label>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-primary w-100">Сохранить</button>
                                </div>
                            </form>

                            <div class="border-top mt-3 pt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <small class="text-muted">Удалить можно только пустой бассейн без дорожек, сеансов и истории. Рабочий бассейн лучше отключить.</small>
                                <form method="post" action="{{ route('admin.pool.zones.store') }}" onsubmit="return confirm('Удалить бассейн / зону «{{ addslashes($zone->name) }}»? Это возможно только если связанных данных нет.');">
                                    @csrf
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="zone_id" value="{{ $zone->id }}">
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Удалить</button>
                                </form>
                            </div>
                        </details>

                        <div class="table-responsive">
                            <table class="table mini-table align-middle">
                                <thead>
                                <tr>
                                    <th>Дорожка</th>
                                    <th>Длина</th>
                                    <th>Лимит</th>
                                    <th>Статус</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($zone->lanes as $lane)
                                    <tr>
                                        <td><strong>{{ $lane->name }}</strong><br><small>№{{ $lane->number }}</small></td>
                                        <td>{{ $lane->length_meters }} м</td>
                                        <td>
                                            <form method="post" action="{{ route('admin.pool.lanes.update', $lane) }}" class="d-flex gap-2 align-items-center">
                                                @csrf
                                                @method('patch')
                                                <input type="number" class="form-control form-control-sm" name="capacity" value="{{ $lane->capacity }}" style="width:80px">
                                        </td>
                                        <td>
                                                <select class="form-select form-select-sm" name="status">
                                                    <option value="open" {{ $lane->status === 'open' ? 'selected' : '' }}>Открыта</option>
                                                    <option value="reserved" {{ $lane->status === 'reserved' ? 'selected' : '' }}>Резерв</option>
                                                    <option value="maintenance" {{ $lane->status === 'maintenance' ? 'selected' : '' }}>ТО</option>
                                                    <option value="closed" {{ $lane->status === 'closed' ? 'selected' : '' }}>Закрыта</option>
                                                </select>
                                        </td>
                                        <td class="text-nowrap">
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input me-2" type="checkbox" name="is_active" value="1" {{ $lane->is_active ? 'checked' : '' }}>
                                                <button class="btn btn-sm btn-dark"><i class="bi bi-check"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted text-center py-3">Дорожек пока нет.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="admin-card p-5 text-center text-muted">Создайте первый бассейн.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="sessions">
        <div class="admin-card p-4">
            <h3>Ближайшие сеансы</h3>
            <div class="table-responsive">
                <table class="table mini-table align-middle">
                    <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Сеанс</th>
                        <th>Загрузка</th>
                        <th>Дорожки</th>
                        <th>Назначить</th>
                        <th>Лист ожидания</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($slots as $slot)
                        <tr>
                            <td>{{ $slot->starts_at->format('d.m') }}<br><strong>{{ $slot->starts_at->format('H:i') }}</strong></td>
                            <td>
                                {{ $slot->service->name }}
                                <br><small>{{ $slot->trainer?->name }} {{ $slot->zone ? '· '.$slot->zone->name : '' }}</small>
                            </td>
                            <td>
                                <strong>{{ $slot->booked_count }}/{{ $slot->capacity }}</strong>
                                <div class="progress" style="height:5px">
                                    <div class="progress-bar" style="width:{{ $slot->capacity ? min(100, $slot->booked_count / $slot->capacity * 100) : 0 }}%"></div>
                                </div>
                            </td>
                            <td>
                                @forelse($slot->lanes as $lane)
                                    <span class="lane-chip {{ $lane->status }}">
                                        {{ $lane->number }}
                                        <form method="post" action="{{ route('admin.pool.slots.lanes.destroy', [$slot, $lane]) }}" class="d-inline">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-link p-0 text-danger">×</button>
                                        </form>
                                    </span>
                                @empty
                                    <span class="text-muted">не назначены</span>
                                @endforelse
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.pool.slots.lanes.store', $slot) }}" class="d-flex gap-1">
                                    @csrf
                                    <select class="form-select form-select-sm" name="pool_lane_id" required>
                                        <option value="">дорожка</option>
                                        @foreach($zones as $zone)
                                            @foreach($zone->lanes->where('is_active', true) as $lane)
                                                <option value="{{ $lane->id }}">{{ $zone->code }} / №{{ $lane->number }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">+</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.pool.waitlist.store', $slot) }}" class="d-flex gap-1 mb-2">
                                    @csrf
                                    <select class="form-select form-select-sm" name="customer_id" required>
                                        <option value="">клиент</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" class="form-control form-control-sm" name="people" value="1" min="1" style="width:60px">
                                    <button class="btn btn-sm btn-outline-secondary">+</button>
                                </form>

                                @foreach($slot->waitlist->where('status', 'waiting') as $entry)
                                    <div class="d-flex justify-content-between gap-2 small border-top pt-1">
                                        <span>{{ $entry->customer->name }}</span>
                                        @if($slot->available_places >= $entry->people)
                                            <form method="post" action="{{ route('admin.pool.waitlist.promote', [$slot, $entry]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success py-0">Записать</button>
                                            </form>
                                        @else
                                            <span class="text-muted">ждёт</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Нет сеансов на ближайшие 7 дней.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="water">
        <div class="row g-4">
            <div class="col-xl-4">
                <div class="admin-card p-4">
                    <h3>Замер воды</h3>
                    <form method="post" action="{{ route('admin.pool.water.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <select class="form-select" name="pool_zone_id" required>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><input type="datetime-local" class="form-control" name="measured_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                        <div class="col-6"><label class="form-label">Температура °C</label><input type="number" step="0.1" class="form-control" name="temperature"></div>
                        <div class="col-6"><label class="form-label">pH</label><input type="number" step="0.01" class="form-control" name="ph"></div>
                        <div class="col-6"><label class="form-label">Своб. хлор</label><input type="number" step="0.001" class="form-control" name="free_chlorine"></div>
                        <div class="col-6"><label class="form-label">Redox mV</label><input type="number" step="0.1" class="form-control" name="redox"></div>
                        <div class="col-12"><textarea class="form-control" name="notes" placeholder="Комментарий"></textarea></div>
                        <div class="col-12"><button class="btn btn-primary w-100">Сохранить замер</button></div>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                @foreach($zones as $zone)
                    <div class="admin-card p-4 mb-3">
                        <h3>{{ $zone->name }}</h3>
                        <div class="table-responsive">
                            <table class="table mini-table">
                                <thead><tr><th>Время</th><th>°C</th><th>pH</th><th>Хлор</th><th>Redox</th><th>Комментарий</th></tr></thead>
                                <tbody>
                                @forelse($zone->waterLogs as $log)
                                    <tr>
                                        <td>{{ $log->measured_at->format('d.m H:i') }}</td>
                                        <td>{{ $log->temperature }}</td>
                                        <td>{{ $log->ph }}</td>
                                        <td>{{ $log->free_chlorine }}</td>
                                        <td>{{ $log->redox }}</td>
                                        <td>{{ $log->notes }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">Замеров ещё нет.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="maintenance">
        <div class="row g-4">
            <div class="col-xl-4">
                <div class="admin-card p-4">
                    <h3>Новая задача</h3>
                    <form method="post" action="{{ route('admin.pool.maintenance.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12"><input class="form-control" name="title" placeholder="Промывка фильтра" required></div>
                        <div class="col-12">
                            <select class="form-select" name="type">
                                <option value="maintenance">Техобслуживание</option>
                                <option value="cleaning">Уборка</option>
                                <option value="repair">Ремонт</option>
                                <option value="inspection">Проверка</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <select class="form-select" name="pool_zone_id">
                                <option value="">Весь комплекс</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><input type="datetime-local" class="form-control" name="due_at"></div>
                        <div class="col-12"><textarea class="form-control" name="notes" placeholder="Описание"></textarea></div>
                        <div class="col-12"><button class="btn btn-primary w-100">Создать</button></div>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                @foreach($maintenance as $task)
                    <div class="admin-card p-3 mb-2">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $task->title }}</strong>
                                <div class="small text-muted">{{ $task->zone?->name }} · {{ optional($task->due_at)->format('d.m.Y H:i') }}</div>
                            </div>
                            <form method="post" action="{{ route('admin.pool.maintenance.update', $task) }}" class="d-flex gap-2">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="notes" value="{{ $task->notes }}">
                                <select class="form-select form-select-sm" name="status">
                                    <option value="open" {{ $task->status === 'open' ? 'selected' : '' }}>Открыта</option>
                                    <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>В работе</option>
                                    <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Готово</option>
                                    <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Отмена</option>
                                </select>
                                <button class="btn btn-sm btn-dark">OK</button>
                            </form>
                        </div>
                        @if($task->notes)
                            <p class="mb-0 mt-2 small">{{ $task->notes }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
