@extends('layouts.app')
@section('title', 'Онлайн-запись — Комплекс Греция')
@section('content')
<section class="page-hero"><div class="container"><div class="eyebrow">Онлайн-запись</div><h1>Выберите удобное время</h1><p>Система покажет свободные места на выбранную дату.</p></div></section>
<section class="section-padding pt-4"><div class="container"><div class="booking-shell"><div class="row g-0">
    <div class="col-lg-5 booking-sidebar"><span class="booking-number">01</span><h2>Услуга и время</h2><p>Выберите услугу, дату и свободный слот. Для записи к тренеру его имя будет указано рядом со временем.</p><div class="booking-benefit"><i class="bi bi-check2-circle"></i><span>Только актуальные свободные места</span></div><div class="booking-benefit"><i class="bi bi-check2-circle"></i><span>Без предоплаты в демо-режиме</span></div><div class="booking-benefit"><i class="bi bi-check2-circle"></i><span>Подтверждение администратором</span></div></div>
    <div class="col-lg-7 p-4 p-xl-5">
        <form method="post" action="{{ route('booking.store') }}" id="bookingForm">@csrf
            <div class="row g-4">
                <div class="col-md-7"><label class="form-label">Услуга</label><select class="form-select form-select-lg" id="serviceSelect" required><option value="">Выберите услугу</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(old('service_id', $selectedService) == $service->id)>{{ $service->name }} — {{ number_format($service->price, 0, ',', ' ') }} ₽</option>@endforeach</select></div>
                <div class="col-md-5"><label class="form-label">Дата</label><input type="date" class="form-control form-control-lg" id="bookingDate" min="{{ now()->toDateString() }}" value="{{ old('date', now()->addDay()->toDateString()) }}" required></div>
                <div class="col-12"><label class="form-label d-flex justify-content-between"><span>Свободное время</span><small class="text-muted" id="slotHint">Сначала выберите услугу и дату</small></label><div id="slotsContainer" class="slots-grid"><div class="slot-empty"><i class="bi bi-calendar2-week"></i><span>Здесь появятся доступные часы</span></div></div><input type="hidden" name="schedule_slot_id" id="slotInput" value="{{ old('schedule_slot_id') }}"></div>
                <div class="col-12"><hr><h4 class="mb-1">Контактные данные</h4><p class="text-muted">Нужны для подтверждения записи.</p></div>
                <div class="col-md-6"><label class="form-label">Имя</label><input class="form-control form-control-lg" name="name" value="{{ old('name') }}" required></div>
                <div class="col-md-6"><label class="form-label">Телефон</label><input class="form-control form-control-lg" name="phone" value="{{ old('phone') }}" placeholder="+7 ___ ___-__-__" required></div>
                <div class="col-md-7"><label class="form-label">Email <small class="text-muted">необязательно</small></label><input type="email" class="form-control form-control-lg" name="email" value="{{ old('email') }}"></div>
                <div class="col-md-5"><label class="form-label">Количество гостей</label><input type="number" class="form-control form-control-lg" name="people" min="1" max="10" value="{{ old('people', 1) }}" required></div>
                <div class="col-12"><label class="form-label">Комментарий</label><textarea class="form-control" name="comment" rows="3" placeholder="Возраст ребёнка, пожелания по тренеру или другая информация">{{ old('comment') }}</textarea></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" value="1" id="privacyBooking" required><label class="form-check-label small text-muted" for="privacyBooking">Согласен с <a href="{{ route('privacy') }}">обработкой персональных данных</a></label></div></div>
                <div class="col-12"><button class="btn btn-primary btn-lg w-100 rounded-pill" type="submit" id="bookingSubmit" disabled>Оставить заявку на запись</button></div>
            </div>
        </form>
    </div>
</div></div></div></section>
@endsection
@push('scripts')
<script>
(() => {
    const service = document.getElementById('serviceSelect');
    const date = document.getElementById('bookingDate');
    const container = document.getElementById('slotsContainer');
    const hint = document.getElementById('slotHint');
    const input = document.getElementById('slotInput');
    const submit = document.getElementById('bookingSubmit');
    const endpoint = @json(route('booking.slots'));

    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));

    async function loadSlots() {
        input.value = '';
        submit.disabled = true;
        if (!service.value || !date.value) return;
        container.innerHTML = '<div class="slot-empty"><span class="spinner-border spinner-border-sm"></span><span>Проверяем расписание…</span></div>';
        hint.textContent = 'Загрузка';
        try {
            const response = await fetch(`${endpoint}?service_id=${encodeURIComponent(service.value)}&date=${encodeURIComponent(date.value)}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Не удалось загрузить расписание');
            const slots = await response.json();
            if (!slots.length) {
                container.innerHTML = '<div class="slot-empty"><i class="bi bi-calendar-x"></i><span>На эту дату свободного времени нет. Выберите другой день.</span></div>';
                hint.textContent = 'Нет мест';
                return;
            }
            hint.textContent = `Доступно: ${slots.length}`;
            container.innerHTML = slots.map(slot => `<button type="button" class="slot-button" data-slot="${slot.id}"><strong>${escapeHtml(slot.time)}</strong><small>до ${escapeHtml(slot.ends_at)} · мест: ${slot.places}</small>${slot.trainer ? `<span>${escapeHtml(slot.trainer)}</span>` : ''}</button>`).join('');
            container.querySelectorAll('.slot-button').forEach(button => button.addEventListener('click', () => {
                container.querySelectorAll('.slot-button').forEach(item => item.classList.remove('active'));
                button.classList.add('active'); input.value = button.dataset.slot; submit.disabled = false;
            }));
        } catch (error) {
            container.innerHTML = `<div class="slot-empty text-danger"><i class="bi bi-exclamation-circle"></i><span>${escapeHtml(error.message)}</span></div>`;
            hint.textContent = 'Ошибка';
        }
    }
    service.addEventListener('change', loadSlots); date.addEventListener('change', loadSlots);
    if (service.value && date.value) loadSlots();
})();
</script>
@endpush
