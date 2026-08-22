@extends('admin.layout')

@section('title', $customer->name)
@section('heading', 'Клиент 360°')
@section('eyebrow', $customer->name)

@section('content')
<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <div class="admin-card p-4 h-100">
            <div class="d-flex gap-3 align-items-center">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width:84px;height:84px">
                    @if($customer->photo_path)
                        <img src="{{ asset('storage/'.$customer->photo_path) }}" class="w-100 h-100 object-fit-cover" alt="{{ $customer->name }}">
                    @else
                        <span class="fs-2 fw-bold">{{ mb_substr($customer->name, 0, 1) }}</span>
                    @endif
                </div>
                <div>
                    <h3 class="mb-1">{{ $customer->name }}</h3>
                    <span class="badge text-bg-light border">{{ $customer->source }}</span>
                    @if($preferredTrainer)
                        <small class="d-block mt-1">Тренер: {{ $preferredTrainer->name }}</small>
                    @endif
                </div>
            </div>

            <hr>

            @if($canPersonal)
                <div class="small">
                    <div><strong>Телефон:</strong> {{ $customer->phone }}</div>
                    <div><strong>Email:</strong> {{ $customer->email ?: '—' }}</div>
                    <div>
                        <strong>Дата рождения:</strong> {{ $customer->birth_date?->format('d.m.Y') ?: '—' }}
                        @if($customer->age())
                            ({{ $customer->age() }} лет)
                        @endif
                    </div>
                    <div><strong>Экстренный контакт:</strong> {{ $customer->emergency_contact ?: '—' }}</div>
                </div>
            @else
                <div class="alert alert-secondary mb-0">Персональные данные скрыты текущими правами.</div>
            @endif

            <hr>

            <div class="row text-center">
                <div class="col">
                    <strong class="d-block fs-5">{{ $customer->visits->count() }}</strong>
                    <small>визитов</small>
                </div>
                <div class="col">
                    <strong class="d-block fs-5">{{ $customer->bookings->count() }}</strong>
                    <small>записей</small>
                </div>
                <div class="col">
                    <strong class="d-block fs-5">{{ $customer->orders->count() }}</strong>
                    <small>заказов</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="row g-3 h-100">
            <div class="col-md-4">
                <div class="admin-card p-3 h-100">
                    <small class="text-muted">Депозит</small>
                    <div class="fs-3 fw-bold">{{ number_format((float) optional($customer->wallet)->deposit_balance, 0, ',', ' ') }} ₽</div>
                    <small>Бонусы: {{ number_format((float) optional($customer->wallet)->bonus_balance, 0, ',', ' ') }}</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="admin-card p-3 h-100">
                    <small class="text-muted">Неоплачено</small>
                    <div class="fs-3 fw-bold {{ $debt > 0 ? 'text-danger' : '' }}">{{ number_format($debt, 0, ',', ' ') }} ₽</div>
                    <small>{{ $customer->orders->whereNotIn('payment_status', ['paid', 'refunded'])->count() }} заказ(а)</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="admin-card p-3 h-100">
                    <small class="text-muted">Активные членства</small>
                    <div class="fs-3 fw-bold">{{ $customer->memberships->where('status', 'active')->count() }}</div>
                    <small>Последний визит: {{ $customer->last_visit_at?->format('d.m.Y H:i') ?: '—' }}</small>
                </div>
            </div>

            <div class="col-12">
                <div class="admin-card p-3">
                    <h5>Семья и дети</h5>
                    @forelse($customer->families as $family)
                        <a class="badge text-bg-light border text-decoration-none p-2" href="{{ route('admin.families.show', $family) }}">
                            {{ $family->name }} · {{ $family->members->count() }} чел.
                        </a>
                    @empty
                        <span class="text-muted">Не привязан к семейному аккаунту</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card p-4 mb-4">
    <ul class="nav nav-pills gap-2" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#timeline">История</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#memberships">Абонементы</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bookings">Записи</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sales">Покупки</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#training">Тренировки</button></li>
        @if(auth()->user()->hasPermission('medical.view'))
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#medical">Медицина</button></li>
        @endif
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes">Заметки и цели</button></li>
    </ul>
</div>

@php
    $events = collect();

    foreach ($customer->visits as $visit) {
        $events->push([
            'at' => $visit->visited_at,
            'title' => 'Посещение',
            'text' => $visit->notes ?: $visit->source,
            'icon' => 'bi-door-open',
        ]);
    }

    foreach ($customer->interactions as $interaction) {
        $events->push([
            'at' => $interaction->occurred_at,
            'title' => 'Контакт · '.$interaction->channel,
            'text' => trim(($interaction->subject ?? '').' '.($interaction->body ?? '')),
            'icon' => 'bi-chat-left-text',
        ]);
    }

    foreach ($customer->bookings as $booking) {
        $events->push([
            'at' => $booking->created_at,
            'title' => 'Запись · '.($booking->service?->name ?? ''),
            'text' => ($booking->slot?->starts_at?->format('d.m.Y H:i') ?? '').' · '.$booking->status,
            'icon' => 'bi-calendar-check',
        ]);
    }

    foreach ($customer->orders as $order) {
        $events->push([
            'at' => $order->created_at,
            'title' => 'Заказ '.$order->number,
            'text' => number_format($order->total, 0, ',', ' ').' ₽ · '.$order->payment_status,
            'icon' => 'bi-receipt',
        ]);
    }

    foreach ($customer->staffNotes as $note) {
        $events->push([
            'at' => $note->created_at,
            'title' => 'Заметка · '.$note->type,
            'text' => $note->body.' · '.($note->user?->name ?? ''),
            'icon' => 'bi-sticky',
        ]);
    }

    $timelineEvents = $events->sortByDesc('at')->take(100);
    $sortedBookings = $customer->bookings->sortByDesc(function ($booking) {
        return $booking->slot?->starts_at;
    });
@endphp

<div class="tab-content">
    <div class="tab-pane fade show active" id="timeline">
        <div class="admin-card p-4">
            <h3>Единая хронология</h3>
            @forelse($timelineEvents as $event)
                <div class="d-flex gap-3 border-bottom py-3">
                    <i class="bi {{ $event['icon'] }} fs-4"></i>
                    <div>
                        <strong>{{ $event['title'] }}</strong>
                        <small class="d-block text-muted">{{ optional($event['at'])->format('d.m.Y H:i') }}</small>
                        <div>{{ $event['text'] }}</div>
                    </div>
                </div>
            @empty
                <p class="text-muted">История пока пуста.</p>
            @endforelse
        </div>
    </div>

    <div class="tab-pane fade" id="memberships">
        <div class="admin-card p-4">
            <h3>Абонементы и заморозки</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Номер</th>
                        <th>Тариф</th>
                        <th>Срок</th>
                        <th>Остаток</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($customer->memberships as $membership)
                        <tr>
                            <td>{{ $membership->number }}</td>
                            <td>
                                {{ $membership->plan?->name }}
                                @if($membership->family)
                                    <small class="d-block">Семья: {{ $membership->family->name }}</small>
                                @endif
                            </td>
                            <td>{{ $membership->starts_on->format('d.m.Y') }}–{{ $membership->ends_on->format('d.m.Y') }}</td>
                            <td>{{ $membership->visits_total === null ? '∞' : max(0, $membership->visits_total - $membership->visits_used) }}</td>
                            <td>
                                {{ $membership->status }}
                                @foreach($membership->freezes as $freeze)
                                    <small class="d-block">заморозка {{ $freeze->starts_on->format('d.m') }}–{{ $freeze->ends_on->format('d.m') }}</small>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="bookings">
        <div class="admin-card p-4">
            <h3>Записи и посещения</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Услуга</th>
                        <th>Тренер</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sortedBookings as $booking)
                        <tr>
                            <td>{{ $booking->slot?->starts_at?->format('d.m.Y H:i') }}</td>
                            <td>{{ $booking->service?->name }}</td>
                            <td>{{ $booking->trainer?->name }}</td>
                            <td>{{ $booking->status }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="sales">
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="admin-card p-4">
                    <h3>Заказы</h3>
                    @foreach($customer->orders as $order)
                        <div class="border-bottom py-2">
                            <strong>{{ $order->number }}</strong> · {{ number_format($order->total, 0, ',', ' ') }} ₽
                            <span class="badge text-bg-light">{{ $order->payment_status }}</span>
                            <small class="d-block">
                                @foreach($order->items as $item)
                                    {{ $item->name }} × {{ $item->quantity }};
                                @endforeach
                            </small>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-xl-4">
                <div class="admin-card p-4">
                    <h3>Сертификаты</h3>
                    @foreach($customer->certificates as $certificate)
                        <div class="border-bottom py-2">
                            <strong>{{ $certificate->serial }}</strong>
                            <small class="d-block">{{ number_format($certificate->amount, 0, ',', ' ') }} ₽ · {{ $certificate->status }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="training">
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="admin-card p-4">
                    <h3>Планы тренировок</h3>
                    @foreach($customer->trainingPlans as $plan)
                        <div class="border rounded-3 p-3 mb-2">
                            <strong>{{ $plan->title }}</strong>
                            <small class="d-block">{{ $plan->trainer?->name }} · {{ $plan->status }}</small>
                            <div>{{ $plan->goal }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-xl-5">
                <div class="admin-card p-4">
                    <h3>Школа плавания</h3>
                    @foreach($customer->swimGroupMemberships as $membership)
                        <div class="border-bottom py-2">
                            <strong>{{ $membership->group?->name }}</strong>
                            <small class="d-block">{{ $membership->group?->trainer?->name }} · {{ $membership->status }}</small>
                            @foreach($membership->progress->take(3) as $progress)
                                <div class="small">{{ $progress->recorded_on->format('d.m.Y') }} · {{ $progress->skill }} · {{ $progress->score }}/5</div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->hasPermission('medical.view'))
        <div class="tab-pane fade" id="medical">
            <div class="admin-card p-4">
                <h3>Медицинские допуски</h3>
                @foreach($customer->medicalClearances as $clearance)
                    <div class="border rounded-3 p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $clearance->type }}</strong>
                            <span class="badge {{ $clearance->isValid() ? 'text-bg-success' : 'text-bg-danger' }}">{{ $clearance->status }}</span>
                        </div>
                        <small>
                            Срок: {{ $clearance->expires_on?->format('d.m.Y') ?: 'без срока' }}
                            @if($clearance->access_blocked)
                                · ВХОД ЗАБЛОКИРОВАН: {{ $clearance->blocked_reason }}
                            @endif
                        </small>
                        @if($clearance->restrictions)
                            <div class="mt-2"><strong>Ограничения:</strong> {{ $clearance->restrictions }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="tab-pane fade" id="notes">
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="admin-card p-4">
                    <h3>Заметки персонала</h3>
                    @foreach($customer->staffNotes as $note)
                        <div class="border-bottom py-3">
                            <span class="badge text-bg-light">{{ $note->type }}</span> {{ $note->body }}
                            <small class="d-block text-muted">{{ $note->user?->name }} · {{ $note->created_at->format('d.m.Y H:i') }}</small>
                        </div>
                    @endforeach

                    @if(auth()->user()->hasPermission('customers.notes'))
                        <form method="post" action="{{ route('admin.customers.notes.store', $customer) }}" class="row g-2 mt-3">
                            @csrf
                            <div class="col-md-3">
                                <select class="form-select" name="type">
                                    <option value="note">Общее</option>
                                    <option value="service">Сервис</option>
                                    <option value="sales">Продажи</option>
                                    <option value="trainer">Тренер</option>
                                    <option value="complaint">Жалоба</option>
                                </select>
                            </div>
                            <div class="col-md-7"><textarea class="form-control" name="body" required placeholder="Комментарий"></textarea></div>
                            <div class="col-md-2"><button class="btn btn-primary w-100">Добавить</button></div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="col-xl-5">
                <div class="admin-card p-4">
                    <h3>Цели</h3>
                    @foreach($customer->goals as $goal)
                        <div class="border rounded-3 p-3 mb-2">
                            <strong>{{ $goal->title }}</strong>
                            <div class="progress my-2" style="height:8px">
                                <div class="progress-bar" style="width:{{ $goal->progress_percent }}%"></div>
                            </div>
                            <small>
                                {{ $goal->progress_percent }}% · {{ $goal->status }}
                                @if($goal->trainer)
                                    · {{ $goal->trainer->name }}
                                @endif
                            </small>
                        </div>
                    @endforeach

                    @if(auth()->user()->hasPermission('customers.notes'))
                        <form method="post" action="{{ route('admin.customers.goals.store', $customer) }}" class="row g-2 mt-3">
                            @csrf
                            <div class="col-12"><input class="form-control" name="title" placeholder="Цель" required></div>
                            <div class="col-7">
                                <select class="form-select" name="trainer_id">
                                    <option value="">Без тренера</option>
                                    @foreach($trainers as $trainer)
                                        <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5"><input type="number" min="0" max="100" value="0" class="form-control" name="progress_percent"></div>
                            <div class="col-12"><textarea class="form-control" name="description" placeholder="Описание"></textarea></div>
                            <div class="col-12"><button class="btn btn-dark">Создать цель</button></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
