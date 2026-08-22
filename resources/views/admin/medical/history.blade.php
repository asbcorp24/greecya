@extends('admin.layout')
@section('title','История меддопуска') @section('heading','История медицинского допуска') @section('eyebrow',$clearance->customer?->name)
@section('content')
<div class="admin-card p-4"><div class="d-flex justify-content-between align-items-center mb-4"><div><h3>{{ $clearance->type }}</h3><p class="text-muted mb-0">Текущий статус: {{ $clearance->status }} · {{ $clearance->access_blocked?'вход заблокирован':'вход не заблокирован' }}</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.medical.index') }}">Назад</a></div>
<div class="timeline">@forelse($clearance->history as $h)<div class="border-start border-3 ps-3 pb-4"><strong>{{ $h->from_status?:'создано' }} → {{ $h->to_status }}</strong> @if($h->access_blocked)<span class="badge text-bg-danger">вход заблокирован</span>@endif<small class="d-block text-muted">{{ $h->changed_at?->format('d.m.Y H:i:s') }} · {{ $h->user?->name?:'Система' }}</small><div>{{ $h->reason }}</div></div>@empty<p class="text-muted">История пока пуста.</p>@endforelse</div></div>
@endsection
