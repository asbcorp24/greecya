@extends('admin.layout')
@section('title','Склад')
@section('heading','Склад, партии и сроки')
@section('eyebrow','Химия, реагенты, расходники и товары')

@section('content')
@if($expiring->count())
    <div class="alert alert-warning rounded-4">
        <strong>Партии с истекающим сроком ≤90 дней:</strong>
        @foreach($expiring as $batch)
            <span class="badge text-bg-warning ms-1">
                {{ $batch->item?->name }} · {{ $batch->batch_number }} · {{ $batch->expires_on->format('d.m.Y') }}
            </span>
        @endforeach
    </div>
@endif

<div class="row g-4">
    <div class="col-xl-4">
        <div class="admin-card p-4">
            <h3>Новая позиция</h3>
            <form method="post" action="{{ route('admin.inventory.store') }}" class="row g-2">
                @csrf
                <div class="col-5"><input class="form-control" name="sku" placeholder="SKU" required></div>
                <div class="col-7"><input class="form-control" name="name" placeholder="Наименование" required></div>
                <div class="col-8">
                    <select class="form-select" name="category">
                        <option value="retail">Розница</option>
                        <option value="pool">Бассейн</option>
                        <option value="chemical">Химия</option>
                        <option value="reagent">Реагент</option>
                        <option value="cleaning">Уборка</option>
                        <option value="spa">SPA</option>
                    </select>
                </div>
                <div class="col-4"><input class="form-control" name="unit" value="шт"></div>
                <div class="col-6"><input type="number" step="0.01" class="form-control" name="purchase_price" value="0" placeholder="Закупка"></div>
                <div class="col-6"><input type="number" step="0.01" class="form-control" name="sale_price" value="0" placeholder="Продажа"></div>
                <div class="col-6"><input type="number" step="0.001" class="form-control" name="stock_qty" value="0" placeholder="Нач. остаток"></div>
                <div class="col-6"><input type="number" step="0.001" class="form-control" name="min_stock" value="0" placeholder="Мин. остаток"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Добавить позицию</button></div>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="admin-card p-4">
            <h3>Остатки и партии</h3>
            @foreach($items as $item)
                <details class="border rounded-4 p-3 mb-3" {{ in_array($item->category,['chemical','reagent'],true) ? 'open' : '' }}>
                    <summary class="d-flex justify-content-between align-items-center">
                        <span><strong>{{ $item->name }}</strong> <small>{{ $item->sku }} · {{ $item->category }}</small></span>
                        <span class="{{ (float)$item->stock_qty <= (float)$item->min_stock ? 'text-danger' : 'text-success' }} fw-bold">
                            {{ $item->stock_qty }} {{ $item->unit }}
                        </span>
                    </summary>

                    <div class="row g-3 mt-1">
                        <div class="col-lg-7">
                            <h6>Партии</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Партия</th><th>Остаток</th><th>Годен до</th><th>Поставщик</th></tr></thead>
                                    <tbody>
                                    @forelse($item->batches as $batch)
                                        <tr class="{{ $batch->expires_on && $batch->expires_on->lte(today()->addDays(30)) ? 'table-warning' : '' }}">
                                            <td>{{ $batch->batch_number }}</td>
                                            <td>{{ $batch->remaining_qty }}</td>
                                            <td>{{ $batch->expires_on?->format('d.m.Y') ?: '—' }}</td>
                                            <td>{{ $batch->supplier }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">Партий нет</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <form method="post" action="{{ route('admin.inventory.batches.store',$item) }}" class="row g-1">
                                @csrf
                                <div class="col-4"><input class="form-control form-control-sm" name="batch_number" placeholder="№ партии" required></div>
                                <div class="col-4"><input class="form-control form-control-sm" name="supplier" placeholder="Поставщик"></div>
                                <div class="col-4"><input class="form-control form-control-sm" name="document_number" placeholder="Документ"></div>
                                <div class="col-4"><input type="date" class="form-control form-control-sm" name="manufactured_on" title="Произведено"></div>
                                <div class="col-4"><input type="date" class="form-control form-control-sm" name="expires_on" title="Годен до"></div>
                                <div class="col-4"><input type="datetime-local" class="form-control form-control-sm" name="received_at" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                                <div class="col-4"><input type="number" step="0.001" class="form-control form-control-sm" name="received_qty" placeholder="Количество" required></div>
                                <div class="col-4"><input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost" value="{{ $item->purchase_price }}" placeholder="Цена"></div>
                                <div class="col-4"><button class="btn btn-sm btn-success w-100">Принять партию</button></div>
                            </form>
                        </div>

                        <div class="col-lg-5">
                            <h6>Расход / корректировка</h6>
                            <form method="post" action="{{ route('admin.inventory.movements.store',$item) }}" class="row g-2">
                                @csrf
                                <div class="col-6">
                                    <select class="form-select form-select-sm" name="type">
                                        <option value="out">Расход</option>
                                        <option value="sale">Продажа</option>
                                        <option value="adjustment">Корректировка +/-</option>
                                    </select>
                                </div>
                                <div class="col-6"><input type="number" step="0.001" class="form-control form-control-sm" name="quantity" placeholder="Кол-во" required></div>
                                <div class="col-12">
                                    <select class="form-select form-select-sm" name="inventory_batch_id">
                                        <option value="">FEFO автоматически</option>
                                        @foreach($item->batches as $batch)
                                            <option value="{{ $batch->id }}">
                                                {{ $batch->batch_number }} · {{ $batch->remaining_qty }} · до {{ $batch->expires_on?->format('d.m.Y') ?: '∞' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <select class="form-select form-select-sm" name="pool_zone_id">
                                        <option value="">Без привязки к бассейну</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12"><input class="form-control form-control-sm" name="note" placeholder="Назначение / основание"></div>
                                <div class="col-12"><button class="btn btn-sm btn-dark">Провести движение</button></div>
                            </form>
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</div>

<div class="admin-card p-4 mt-4">
    <h3>Журнал движений</h3>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Дата</th><th>Товар</th><th>Партия</th><th>Зона</th><th>Тип</th><th>Количество</th><th>Комментарий</th></tr></thead>
            <tbody>
            @foreach($movements as $movement)
                <tr>
                    <td>{{ $movement->occurred_at->format('d.m.Y H:i') }}</td>
                    <td>{{ $movement->item?->name }}</td>
                    <td>{{ $movement->batch?->batch_number ?: '—' }}</td>
                    <td>{{ $movement->zone?->name ?: '—' }}</td>
                    <td>{{ $movement->type }}</td>
                    <td class="{{ (float)$movement->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity }}</td>
                    <td>{{ $movement->note }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
