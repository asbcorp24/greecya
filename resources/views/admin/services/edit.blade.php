@extends('admin.layout')

@section('title','Редактирование услуги')
@section('heading','Редактирование услуги')
@section('eyebrow','Услуги комплекса')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">{{ $service->name }}</h2>
        <div class="text-muted">Изменения цены и параметров применяются к новым операциям. Уже созданная история записей сохраняется.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.services.index') }}"><i class="bi bi-arrow-left me-1"></i>К списку</a>
        @if(auth()->user()->hasPermission('bookings.manage'))
            <a class="btn btn-outline-primary" href="{{ route('admin.schedule.index') }}"><i class="bi bi-calendar3 me-1"></i>Расписание</a>
        @endif
    </div>
</div>

<form method="post" action="{{ route('admin.services.update', $service) }}">
    @csrf
    @method('patch')
    @include('admin.services._form')
</form>
@endsection
