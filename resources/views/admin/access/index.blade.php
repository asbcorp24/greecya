@extends('admin.layout')

@section('title','СКУД и проход')
@section('heading','СКУД, проход и раздевалки')
@section('eyebrow','Контроль доступа бассейна')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="admin-card p-4 access-result">
            <h3><i class="bi bi-qr-code-scan me-2"></i>Стойка прохода</h3>
            <form method="post" action="{{ route('admin.access.checkin') }}" class="row g-2">
                @csrf
                <div class="col-12">
                    <input class="form-control form-control-lg" name="code" placeholder="Сканируйте QR / карту">
                </div>
                <div class="col-12 text-center text-muted small">или выберите клиента вручную</div>
                <div class="col-12">
                    <select class="form-select" name="customer_id">
                        <option value="">Клиент</option>
                        @foreach($allCustomers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} · {{ $c->phone }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8">
                    <select class="form-select" name="pool_zone_id" required>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <select class="form-select" name="event_type">
                        <option value="enter">Вход</option>
                        <option value="exit">Выход</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-success btn-lg w-100">Проверить и пропустить</button>
                </div>
            </form>
            <p class="small text-muted mt-3 mb-0">Для бассейна проверяется действующий медицинский допуск и активное членство/пакет либо запись на текущий день.</p>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="admin-card p-4">
            <h3>Найти клиента</h3>
            <form class="d-flex gap-2 mb-3">
                <input class="form-control" name="q" value="{{ request('q') }}" placeholder="ФИО, телефон, email или код карты">
                <button class="btn btn-primary">Найти</button>
            </form>

            @forelse($customers as $customer)
                <div class="border rounded-4 p-3 mb-3">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <strong>{{ $customer->name }}</strong><br>
                            <small>{{ $customer->phone }} · {{ $customer->email }}</small>
                        </div>
                        <div>
                            @foreach($customer->memberships as $membership)
                                <span class="badge {{ $membership->isUsable() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $membership->plan->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <strong class="small">Карты доступа</strong>
                            @forelse($customer->accessCards as $card)
                                <div class="small">{{ $card->code }} · {{ $card->status }}</div>
                            @empty
                                <div class="small text-muted">нет</div>
                            @endforelse

                            <form method="post" action="{{ route('admin.access.cards.store',$customer) }}" class="d-flex gap-1 mt-2">
                                @csrf
                                <input class="form-control form-control-sm" name="code" placeholder="код или пусто">
                                <select class="form-select form-select-sm" name="type">
                                    <option value="qr">QR</option>
                                    <option value="nfc">NFC</option>
                                    <option value="barcode">Штрихкод</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Выдать</button>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <strong class="small">Меддопуск</strong>
                            @php($valid = $customer->medicalClearances->sortByDesc('expires_on')->first())
                            <div class="small {{ $valid && $valid->isValid() ? 'text-success' : 'text-danger' }}">
                                {{ $valid && $valid->isValid() ? 'действует до '.optional($valid->expires_on)->format('d.m.Y') : 'нет действующего' }}
                            </div>
                            <form method="post" action="{{ route('admin.access.medical.store',$customer) }}" class="row g-1 mt-2">
                                @csrf
                                <input type="hidden" name="type" value="pool">
                                <div class="col-6"><input type="date" class="form-control form-control-sm" name="issued_on" value="{{ date('Y-m-d') }}"></div>
                                <div class="col-6"><input type="date" class="form-control form-control-sm" name="expires_on" required></div>
                                <div class="col-12"><button class="btn btn-sm btn-outline-success w-100">Добавить допуск</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                @if(request('q'))
                    <div class="text-muted">Клиенты не найдены.</div>
                @else
                    <div class="text-muted">Введите данные клиента для просмотра карт, членств и допусков.</div>
                @endif
            @endforelse
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="admin-card p-4 mb-4">
            <h3>Шкафчики</h3>
            <form method="post" action="{{ route('admin.access.lockers.store') }}" class="row g-2 mb-3">
                @csrf
                <div class="col-4"><input class="form-control" name="number" placeholder="№ 101" required></div>
                <div class="col-4"><input class="form-control" name="zone" value="раздевалка"></div>
                <div class="col-4">
                    <select class="form-select" name="gender">
                        <option value="unisex">Общий</option>
                        <option value="male">М</option>
                        <option value="female">Ж</option>
                    </select>
                </div>
                <div class="col-12"><button class="btn btn-outline-primary w-100">Добавить шкафчик</button></div>
            </form>

            <form method="post" action="{{ route('admin.access.lockers.assign') }}" class="row g-2">
                @csrf
                <div class="col-6">
                    <select class="form-select" name="locker_id" required>
                        <option value="">Свободный шкафчик</option>
                        @foreach($lockers->where('status','available') as $locker)
                            <option value="{{ $locker->id }}">№{{ $locker->number }} · {{ $locker->zone }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-select" name="customer_id" required>
                        <option value="">Клиент</option>
                        @foreach($allCustomers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6"><input type="datetime-local" class="form-control" name="ends_at"></div>
                <div class="col-6"><input type="number" class="form-control" name="deposit" value="0" placeholder="Залог"></div>
                <div class="col-12"><button class="btn btn-dark w-100">Выдать шкафчик</button></div>
            </form>

            <hr>
            @foreach($rentals as $rental)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><strong>№{{ $rental->locker->number }}</strong> · {{ $rental->customer->name }}</span>
                    <form method="post" action="{{ route('admin.access.lockers.return',$rental) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-success">Вернуть</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-xl-7">
        <div class="admin-card p-4">
            <h3>Последние события доступа</h3>
            <div class="table-responsive">
                <table class="table mini-table">
                    <thead><tr><th>Время</th><th>Клиент</th><th>Зона</th><th>Событие</th><th>Результат</th><th>Причина</th></tr></thead>
                    <tbody>
                    @foreach($events as $event)
                        <tr>
                            <td>{{ $event->occurred_at->format('d.m H:i:s') }}</td>
                            <td>{{ $event->customer->name }}</td>
                            <td>{{ $event->zone?->name }}</td>
                            <td>{{ $event->event_type }}</td>
                            <td><span class="badge {{ $event->result === 'allowed' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $event->result }}</span></td>
                            <td>{{ $event->reason }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
