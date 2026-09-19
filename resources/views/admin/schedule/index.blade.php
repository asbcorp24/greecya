@extends('admin.layout')
@section('title', 'Расписание')
@section('heading', 'Расписание')
@section('eyebrow', 'Свободные слоты')

@section('content')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="sticky-xl-top d-grid gap-4" style="top:1rem">
            <div class="admin-card p-4">
                <h3>Добавить время</h3>
                <p class="text-muted">Слот сразу появится на странице онлайн-записи для услуг с обычным расписанием.</p>

                <form method="post" action="{{ route('admin.schedule.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Услуга</label>
                        <select class="form-select" name="service_id" required>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Тренер</label>
                        <select class="form-select" name="trainer_id">
                            <option value="">Без тренера</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Начало</label>
                        <input type="datetime-local" class="form-control" name="starts_at" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Окончание</label>
                        <input type="datetime-local" class="form-control" name="ends_at" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Вместимость</label>
                        <input type="number" class="form-control" name="capacity" min="1" max="100" value="1" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100">Добавить в расписание</button>
                    </div>
                </form>
            </div>

            <div class="admin-card p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="fs-3 text-primary"><i class="bi bi-infinity"></i></span>
                    <div>
                        <h3 class="mb-1">Без ограничений</h3>
                        <p class="text-muted mb-0">Если включено, посетитель после выбора даты сможет указать любое время. Создавать отдельные слоты для этой услуги не требуется.</p>
                    </div>
                </div>

                <div class="d-grid gap-3">
                    @foreach($services as $service)
                        <form method="post" action="{{ route('admin.schedule.unrestricted', $service) }}" class="border rounded-3 p-3">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="unrestricted_booking" value="0">

                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="min-w-0">
                                    <strong class="d-block">{{ $service->name }}</strong>
                                    <small class="{{ $service->online_booking ? 'text-muted' : 'text-danger' }}">
                                        {{ $service->online_booking ? 'Онлайн-запись включена' : 'Онлайн-запись выключена' }}
                                    </small>
                                </div>

                                <div class="form-check form-switch m-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        name="unrestricted_booking"
                                        value="1"
                                        id="unrestricted-{{ $service->id }}"
                                        @checked($service->unrestricted_booking)
                                        onchange="this.form.submit()"
                                    >
                                    <label class="visually-hidden" for="unrestricted-{{ $service->id }}">Без ограничений</label>
                                </div>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3>Предстоящие слоты</h3>
                    <p>Занято / всего мест. Технические слоты записей «без ограничений» здесь не показываются.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                    <tr>
                        <th>Дата и время</th>
                        <th>Услуга</th>
                        <th>Тренер</th>
                        <th>Места</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($slots as $slot)
                        <tr>
                            <td>
                                <strong>{{ $slot->starts_at->format('d.m.Y H:i') }}</strong>
                                <small>до {{ $slot->ends_at->format('H:i') }}</small>
                            </td>
                            <td>
                                {{ $slot->service->name }}
                                @if($slot->service->unrestricted_booking)
                                    <span class="badge text-bg-primary ms-1">без ограничений</span>
                                @endif
                            </td>
                            <td>{{ $slot->trainer?->name ?? '—' }}</td>
                            <td><span class="count-badge">{{ $slot->booked_count }} / {{ $slot->capacity }}</span></td>
                            <td>
                                @if($slot->booked_count===0)
                                    <form method="post" action="{{ route('admin.schedule.destroy',$slot) }}" onsubmit="return confirm('Удалить время?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">Расписание пусто</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">{{ $slots->links() }}</div>
        </div>
    </div>
</div>
@endsection
