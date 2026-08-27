@extends('admin.layout')

@section('title','Новая услуга')
@section('heading','Новая услуга')
@section('eyebrow','Услуги комплекса')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Создание услуги</h2>
        <div class="text-muted">После сохранения услугу можно использовать при создании расписания.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.services.index') }}"><i class="bi bi-arrow-left me-1"></i>К списку</a>
</div>

<form method="post" action="{{ route('admin.services.store') }}">
    @csrf
    @include('admin.services._form')
</form>
@endsection
