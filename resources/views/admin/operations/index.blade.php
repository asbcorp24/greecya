@extends('admin.layout')

@section('title', 'Эксплуатация')
@section('heading', 'Эксплуатация бассейна')
@section('eyebrow', 'Вода, нормативы, реагенты и чек-листы')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach($zones as $z)
        <a class="btn {{ $selectedZone == $z->id ? 'btn-primary' : 'btn-outline-primary' }}"
           href="{{ route('admin.operations.index', ['zone' => $z->id]) }}">
            {{ $z->name }}
        </a>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="admin-card p-4">
            <div class="d-flex justify-content-between">
                <div>
                    <h3>Динамика воды</h3>
                    <p class="text-muted">Последние {{ $readings->count() }} замеров выбранной зоны</p>
                </div>
            </div>
            <canvas id="waterChart" height="110"></canvas>
            <div class="d-flex flex-wrap gap-3 small mt-3">
                <span>● Температура °C</span>
                <span>● pH</span>
                <span>● Свободный хлор</span>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 h-100">
            <h3>Новый замер</h3>
            <form method="post" action="{{ route('admin.operations.water.store') }}" class="row g-2">
                @csrf
                <div class="col-12">
                    <select class="form-select" name="pool_zone_id" required>
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}" @selected($selectedZone == $z->id)>{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><input type="datetime-local" class="form-control" name="measured_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-6"><input type="number" step="0.01" class="form-control" name="temperature" placeholder="Температура"></div>
                <div class="col-6"><input type="number" step="0.01" class="form-control" name="ph" placeholder="pH"></div>
                <div class="col-6"><input type="number" step="0.001" class="form-control" name="free_chlorine" placeholder="Хлор"></div>
                <div class="col-6"><input type="number" step="0.01" class="form-control" name="redox" placeholder="Redox"></div>
                <div class="col-6"><input type="number" step="0.001" class="form-control" name="turbidity" placeholder="Мутность"></div>
                <div class="col-12"><textarea class="form-control" name="notes" placeholder="Комментарий"></textarea></div>
                <div class="col-12"><button class="btn btn-primary w-100">Сохранить и проверить</button></div>
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="admin-card p-4">
            <h3>Предупреждения</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Время</th>
                        <th>Зона</th>
                        <th>Параметр</th>
                        <th>Значение</th>
                        <th>Норма</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($alerts as $a)
                        <tr>
                            <td>{{ $a->created_at->format('d.m H:i') }}</td>
                            <td>{{ $a->zone?->name }}</td>
                            <td><span class="badge {{ $a->severity === 'critical' ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $a->parameter }}</span></td>
                            <td>{{ $a->actual_value }}</td>
                            <td>{{ $a->expected_range }}</td>
                            <td>
                                @if($a->status === 'open')
                                    <form method="post" action="{{ route('admin.operations.alerts.update', $a) }}" class="d-flex gap-1">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="status" value="resolved">
                                        <input class="form-control form-control-sm" name="notes" placeholder="Что сделано">
                                        <button class="btn btn-sm btn-success">Закрыть</button>
                                    </form>
                                @else
                                    {{ $a->status }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Отклонений нет.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="admin-card p-4">
            <h3>Нормативные диапазоны</h3>
            @foreach($zones->where('type', 'pool') as $z)
                @php($n = $norms->get($z->id))
                <details class="border rounded-3 p-2 mb-2" @if($selectedZone == $z->id) open @endif>
                    <summary class="fw-bold">{{ $z->name }}</summary>
                    <form method="post" action="{{ route('admin.operations.norm.update', $z) }}" class="row g-2 mt-2">
                        @csrf
                        @method('put')
                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm" name="temperature_min" value="{{ $n?->temperature_min }}" placeholder="T min"></div>
                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm" name="temperature_max" value="{{ $n?->temperature_max }}" placeholder="T max"></div>
                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm" name="ph_min" value="{{ $n?->ph_min }}" placeholder="pH min"></div>
                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm" name="ph_max" value="{{ $n?->ph_max }}" placeholder="pH max"></div>
                        <div class="col-6"><input type="number" step="0.001" class="form-control form-control-sm" name="free_chlorine_min" value="{{ $n?->free_chlorine_min }}" placeholder="Cl min"></div>
                        <div class="col-6"><input type="number" step="0.001" class="form-control form-control-sm" name="free_chlorine_max" value="{{ $n?->free_chlorine_max }}" placeholder="Cl max"></div>
                        <div class="col-6"><input type="number" step="0.1" class="form-control form-control-sm" name="redox_min" value="{{ $n?->redox_min }}" placeholder="Redox min"></div>
                        <div class="col-6"><input type="number" step="0.1" class="form-control form-control-sm" name="redox_max" value="{{ $n?->redox_max }}" placeholder="Redox max"></div>
                        <div class="col-6"><input type="number" step="0.001" class="form-control form-control-sm" name="turbidity_max" value="{{ $n?->turbidity_max }}" placeholder="Мутность max"></div>
                        <div class="col-6">
                            <label class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="alerts_enabled" value="1" @checked(!$n || $n->alerts_enabled)>
                                alerts
                            </label>
                        </div>
                        <div class="col-12"><button class="btn btn-sm btn-dark">Сохранить норму</button></div>
                    </form>
                </details>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="admin-card p-4">
            <h3>Эксплуатационная операция</h3>
            <form method="post" action="{{ route('admin.operations.log.store') }}" class="row g-2">
                @csrf
                <div class="col-6">
                    <select class="form-select" name="pool_zone_id">
                        <option value="">Зона</option>
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-select" name="pool_lane_id">
                        <option value="">Дорожка / вся зона</option>
                        @foreach($zones as $z)
                            @foreach($z->lanes as $l)
                                <option value="{{ $l->id }}">{{ $z->name }} · {{ $l->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-select" name="type">
                        <option value="filter_backwash">Промывка фильтра</option>
                        <option value="reagent_addition">Внесение реагента</option>
                        <option value="water_topup">Долив воды</option>
                        <option value="cleaning">Уборка</option>
                        <option value="equipment_check">Проверка оборудования</option>
                        <option value="repair">Ремонт</option>
                        <option value="shutdown">Остановка</option>
                        <option value="other">Другое</option>
                    </select>
                </div>
                <div class="col-6"><input type="datetime-local" class="form-control" name="performed_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-12"><textarea class="form-control" name="details" placeholder="Что выполнено"></textarea></div>
                <div class="col-8"><input class="form-control" name="result" placeholder="Результат"></div>
                <div class="col-4"><button class="btn btn-primary w-100">Записать</button></div>
            </form>
            <hr>
            @foreach($operations->take(15) as $op)
                <div class="border-bottom py-2">
                    <strong>{{ $op->type }}</strong> · {{ $op->zone?->name }}
                    <small class="d-block">{{ $op->performed_at->format('d.m.Y H:i') }} · {{ $op->user?->name }} · {{ $op->result }}</small>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-xl-6">
        <div class="admin-card p-4">
            <h3>Расход реагента</h3>
            <form method="post" action="{{ route('admin.operations.chemicals.store') }}" class="row g-2">
                @csrf
                <div class="col-6">
                    <select class="form-select" name="pool_zone_id" required>
                        @foreach($zones->where('type', 'pool') as $z)
                            <option value="{{ $z->id }}">{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-select" name="inventory_item_id" required>
                        <option value="">Реагент</option>
                        @foreach($chemicals as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} · {{ $item->stock_qty }} {{ $item->unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <select class="form-select" name="inventory_batch_id">
                        <option value="">Автоматически: ближайший срок годности</option>
                        @foreach($chemicals as $item)
                            @foreach($item->batches as $b)
                                <option value="{{ $b->id }}">{{ $item->name }} · партия {{ $b->batch_number }} · до {{ $b->expires_on?->format('d.m.Y') ?: '∞' }} · {{ $b->remaining_qty }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-4"><input type="number" step="0.001" class="form-control" name="quantity" placeholder="Количество" required></div>
                <div class="col-8"><input type="datetime-local" class="form-control" name="used_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-12"><input class="form-control" name="purpose" placeholder="Назначение"></div>
                <div class="col-12"><button class="btn btn-dark">Списать реагент</button></div>
            </form>
            <hr>
            @foreach($usages->take(15) as $u)
                <div class="border-bottom py-2">
                    <strong>{{ $u->item?->name }} · {{ $u->quantity }} {{ $u->unit }}</strong>
                    <small class="d-block">{{ $u->zone?->name }} · {{ $u->batch?->batch_number }} · {{ $u->used_at->format('d.m.Y H:i') }}</small>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="admin-card p-4">
            <h3>Новый чек-лист</h3>
            <form method="post" action="{{ route('admin.operations.checklists.store') }}" class="row g-2">
                @csrf
                <div class="col-12"><input class="form-control" name="name" placeholder="Ежедневный обход" required></div>
                <div class="col-6">
                    <select class="form-select" name="type">
                        <option value="daily">Ежедневный</option>
                        <option value="weekly">Еженедельный</option>
                        <option value="shift">Смена</option>
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-select" name="pool_zone_id">
                        <option value="">Все зоны</option>
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><textarea class="form-control" rows="7" name="items" placeholder="Каждый пункт с новой строки" required></textarea></div>
                <div class="col-12"><button class="btn btn-primary">Создать</button></div>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="admin-card p-4">
            <h3>Технические чек-листы</h3>
            @foreach($checklists as $c)
                <details class="border rounded-4 p-3 mb-2">
                    <summary class="fw-bold">{{ $c->name }} · {{ $c->zone?->name ?: 'весь комплекс' }}</summary>
                    <form method="post" action="{{ route('admin.operations.checklists.run', $c) }}" class="mt-3">
                        @csrf
                        @foreach($c->items as $item)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-5">{{ $item->title }}</div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" name="result[{{ $item->id }}]">
                                        <option value="ok">Норма</option>
                                        <option value="issue">Проблема</option>
                                        <option value="not_checked">Не проверено</option>
                                    </select>
                                </div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="comment[{{ $item->id }}]" placeholder="Комментарий"></div>
                            </div>
                        @endforeach
                        <textarea class="form-control mb-2" name="notes" placeholder="Комментарий обхода"></textarea>
                        <button class="btn btn-sm btn-success">Завершить чек-лист</button>
                    </form>
                </details>
            @endforeach
        </div>
    </div>
</div>
@endsection

@php
    $chartRows = $readings->map(function ($reading) {
        return [
            't' => $reading->measured_at->format('d.m H:i'),
            'temperature' => $reading->temperature,
            'ph' => $reading->ph,
            'chlorine' => $reading->free_chlorine,
        ];
    })->values();
@endphp

@push('scripts')
<script>
(() => {
    const rows = @json($chartRows);
    const c = document.getElementById('waterChart');
    if (!c || rows.length < 2) return;

    const ctx = c.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const w = c.clientWidth;
    const h = 260;
    c.width = w * dpr;
    c.height = h * dpr;
    ctx.scale(dpr, dpr);

    const pad = 38;
    ctx.font = '11px sans-serif';
    ctx.strokeStyle = '#dfe5eb';
    for (let i = 0; i < 5; i++) {
        const y = pad + (h - pad * 2) * i / 4;
        ctx.beginPath();
        ctx.moveTo(pad, y);
        ctx.lineTo(w - pad, y);
        ctx.stroke();
    }

    const series = [
        ['temperature', '#0d6efd'],
        ['ph', '#198754'],
        ['chlorine', '#fd7e14'],
    ];

    series.forEach(([key, color]) => {
        const vals = rows.map(r => r[key] === null ? null : Number(r[key])).filter(v => v !== null);
        if (!vals.length) return;

        let min = Math.min(...vals);
        let max = Math.max(...vals);
        if (min === max) {
            min -= 1;
            max += 1;
        }

        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        let started = false;

        rows.forEach((r, i) => {
            if (r[key] === null) return;
            const x = pad + (w - pad * 2) * i / (rows.length - 1);
            const y = h - pad - (Number(r[key]) - min) / (max - min) * (h - pad * 2);
            if (!started) {
                ctx.moveTo(x, y);
                started = true;
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();
    });

    ctx.fillStyle = '#6c757d';
    [0, Math.floor((rows.length - 1) / 2), rows.length - 1].forEach(i => {
        ctx.fillText(rows[i].t, pad + (w - pad * 2) * i / (rows.length - 1) - 20, h - 10);
    });
})();
</script>
@endpush
