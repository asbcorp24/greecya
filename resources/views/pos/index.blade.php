@extends($layout)

@section('title', $mode === 'admin' ? 'Продажа' : 'Первичная продажа')
@section('heading', 'Продажа')
@section('eyebrow', 'Клиенты и продажи')
@section('workspace_name', 'Первичная продажа')

@section('content')
@php
    $pageRoute = $mode === 'admin' ? 'admin.sales.index' : 'reception.sales.create';
    $storeRoute = $mode === 'admin' ? 'admin.sales.store' : 'reception.sales.store';
    $typeLabels = [
        'ticket' => 'Билет',
        'membership' => 'Абонемент',
        'gift' => 'Сертификат',
        'service' => 'Услуга',
    ];
    $phonePrefill = preg_match('/[0-9]{6,}/', $query) ? $query : '';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">{{ $mode === 'admin' ? 'Продажа клиенту' : 'Первичная продажа на ресепшене' }}</h2>
        <div class="text-muted">Новый клиент → билет/абонемент → оплата → касса → проход.</div>
    </div>
    @if($mode === 'reception')
        <a class="btn btn-outline-secondary" href="{{ route('reception.index', $customer ? ['customer' => $customer->id] : []) }}">
            <i class="bi bi-arrow-left"></i> К проходу
        </a>
    @endif
</div>

@if($lastOrder)
    <div class="alert alert-success rounded-4 border-0 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="small text-uppercase fw-bold opacity-75">Последняя продажа</div>
                <h3 class="mb-1">{{ $lastOrder->number }}</h3>
                <div>{{ $lastOrder->customer?->name }} · {{ number_format((float)$lastOrder->total, 0, ',', ' ') }} ₽</div>
                @foreach($lastOrder->items as $item)
                    <div class="small mt-1">{{ $item->name }} · билет/QR: <strong>{{ $item->ticket_code }}</strong></div>
                @endforeach
            </div>
            <div class="text-end">
                <span class="badge text-bg-success fs-6">Оплачено</span>
                <div class="small mt-2">{{ $lastOrder->paid_at?->format('d.m.Y H:i') }}</div>
            </div>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-xl-4">
        <div class="admin-card p-4 mb-4">
            <h3 class="mb-3"><span class="badge text-bg-primary me-2">1</span> Клиент</h3>
            <form method="get" action="{{ route($pageRoute) }}" class="mb-3">
                <div class="input-group">
                    <input class="form-control" name="q" value="{{ $query }}" placeholder="ФИО, телефон или email">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>

            @if($customer)
                <div class="border rounded-4 p-3 bg-light">
                    <div class="small text-muted">Выбран клиент</div>
                    <h4 class="mb-1">{{ $customer->name }}</h4>
                    <div>{{ $customer->phone }}</div>
                    @if($customer->email)<div class="small text-muted">{{ $customer->email }}</div>@endif
                    <a class="btn btn-sm btn-outline-secondary mt-3" href="{{ route($pageRoute) }}">Выбрать другого / создать нового</a>
                </div>
            @elseif($results->isNotEmpty())
                <div class="list-group">
                    @foreach($results as $found)
                        <a class="list-group-item list-group-item-action" href="{{ route($pageRoute, ['q' => $query, 'customer' => $found->id]) }}">
                            <strong>{{ $found->name }}</strong>
                            <small class="d-block text-muted">{{ $found->phone }}{{ $found->email ? ' · '.$found->email : '' }}</small>
                        </a>
                    @endforeach
                </div>
                <div class="small text-muted mt-3">Если нужного клиента нет, очистите поиск и оформите нового.</div>
            @else
                <div class="alert alert-light border mb-0">
                    <strong>Новый клиент</strong>
                    <div class="small text-muted">Заполните данные в форме продажи справа. Клиент будет создан автоматически по телефону.</div>
                </div>
            @endif
        </div>

        <div class="admin-card p-4">
            <h4><i class="bi bi-receipt-cutoff"></i> Последние POS-продажи</h4>
            @forelse($recentSales as $sale)
                <div class="border-bottom py-2">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>{{ $sale->number }}</strong>
                        <span>{{ number_format((float)$sale->total, 0, ',', ' ') }} ₽</span>
                    </div>
                    <small class="text-muted">{{ $sale->customer?->name }} · {{ $sale->paid_at?->format('d.m H:i') }}</small>
                </div>
            @empty
                <p class="text-muted mb-0">Продаж через POS ещё нет.</p>
            @endforelse
        </div>
    </div>

    <div class="col-xl-8">
        <form method="post" action="{{ route($storeRoute) }}" class="admin-card p-4" id="posSaleForm">
            @csrf
            @if($customer)
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            @else
                <h3 class="mb-3"><span class="badge text-bg-primary me-2">2</span> Быстрая регистрация</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">ФИО *</label>
                        <input class="form-control" name="name" value="{{ old('name', preg_match('/[0-9]{6,}/', $query) ? '' : $query) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Телефон *</label>
                        <input class="form-control" name="phone" value="{{ old('phone', $phonePrefill) }}" required placeholder="+7 999 000-00-00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Дата рождения</label>
                        <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date') }}">
                    </div>
                </div>
            @endif

            <h3 class="mb-3"><span class="badge text-bg-primary me-2">{{ $customer ? '2' : '3' }}</span> Билет или абонемент</h3>
            @if($products->isEmpty())
                <div class="alert alert-warning">Нет активных товаров/тарифов. Сначала создайте их в разделе «Товары и тарифы».</div>
            @else
                <div class="row g-3 mb-4">
                    <div class="col-md-9">
                        <label class="form-label">Что продаём *</label>
                        <select class="form-select form-select-lg" name="product_id" id="posProduct" required>
                            <option value="">Выберите билет или тариф</option>
                            @foreach($products as $product)
                                @php($quote = $quotes[$product->id])
                                <option
                                    value="{{ $product->id }}"
                                    data-price="{{ number_format((float)$quote['price'], 2, '.', '') }}"
                                    data-name="{{ $product->name }}"
                                    {{ (string)old('product_id') === (string)$product->id ? 'selected' : '' }}
                                >
                                    {{ $typeLabels[$product->type] ?? ucfirst($product->type) }} · {{ $product->name }} — {{ number_format((float)$quote['price'], 0, ',', ' ') }} ₽
                                    @if((float)$quote['difference'] !== 0.0) · динамическая цена @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Цена пересчитывается по действующим правилам динамического ценообразования.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Количество *</label>
                        <input type="number" class="form-control form-control-lg" id="posQuantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="10" required>
                    </div>
                </div>
            @endif

            <h3 class="mb-3"><span class="badge text-bg-primary me-2">{{ $customer ? '3' : '4' }}</span> Оплата</h3>
            @if($openShifts->isEmpty())
                <div class="alert alert-danger rounded-4">
                    <strong>Нет открытой кассовой смены.</strong>
                    Продажу нельзя завершить без отражения денег в кассе.
                    @if(auth()->user()->hasPermission('cash.view'))
                        <a href="{{ route('admin.finance.index') }}" class="alert-link">Открыть «Касса и платежи»</a>.
                    @else
                        Попросите менеджера или кассира открыть смену.
                    @endif
                </div>
            @else
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Касса / смена *</label>
                        <select class="form-select" name="cash_shift_id" required>
                            @foreach($openShifts as $shift)
                                <option value="{{ $shift->id }}" {{ (string)old('cash_shift_id', $openShifts->count() === 1 ? $shift->id : '') === (string)$shift->id ? 'selected' : '' }}>
                                    {{ $shift->register?->name ?? 'Касса' }} · смена #{{ $shift->id }} · {{ $shift->opened_at?->format('d.m H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Способ оплаты *</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="card" {{ old('payment_method', 'card') === 'card' ? 'selected' : '' }}>Банковская карта</option>
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Наличные</option>
                            <option value="sbp" {{ old('payment_method') === 'sbp' ? 'selected' : '' }}>СБП</option>
                            <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>Безналичный перевод</option>
                        </select>
                    </div>
                </div>
            @endif

            <div class="rounded-4 bg-light border p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="small text-muted">К оплате</div>
                    <div class="display-6 fw-bold" id="posTotal">0 ₽</div>
                    <div class="small text-muted" id="posSummary">Выберите товар</div>
                </div>
                <button class="btn btn-success btn-lg px-5" {{ $openShifts->isEmpty() || $products->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-credit-card"></i>
                    {{ $mode === 'reception' ? 'Оплатить и перейти к проходу' : 'Оплатить и оформить' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const product = document.getElementById('posProduct');
    const quantity = document.getElementById('posQuantity');
    const total = document.getElementById('posTotal');
    const summary = document.getElementById('posSummary');

    const redraw = () => {
        if (!product || !quantity || !total || !summary) return;
        const option = product.options[product.selectedIndex];
        const price = Number(option?.dataset?.price || 0);
        const qty = Math.max(1, Number(quantity.value || 1));
        total.textContent = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(price * qty) + ' ₽';
        summary.textContent = option?.dataset?.name ? option.dataset.name + ' × ' + qty : 'Выберите товар';
    };

    product?.addEventListener('change', redraw);
    quantity?.addEventListener('input', redraw);
    redraw();
})();
</script>
@endpush
