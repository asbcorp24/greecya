@extends('admin.layout')

@section('title', 'Архив бассейнов')
@section('heading', 'Архив бассейнов и дорожек')
@section('eyebrow', 'Safe delete · восстановление без потери истории')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="mb-1">Здесь находятся безопасно удалённые бассейны и дорожки.</p>
        <small class="text-muted">Связанные сеансы, замеры воды, техобслуживание, инциденты, эксплуатационные операции и другие исторические записи не удаляются.</small>
    </div>
    <a href="{{ route('admin.pool.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left me-1"></i>К бассейнам
    </a>
</div>

<div class="alert alert-info border-0 rounded-4 mb-4">
    <strong>Как работает safe delete.</strong>
    Удалённый объект исчезает из рабочих списков, но остаётся в базе с датой удаления и пользователем, выполнившим действие.
    После восстановления бассейн и дорожки намеренно остаются выключенными/закрытыми — их нужно включить вручную после проверки расписания.
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Удалённые бассейны / зоны</h3>
                <span class="badge text-bg-secondary">{{ $archivedZones->count() }}</span>
            </div>

            @forelse($archivedZones as $zone)
                <div class="border rounded-4 p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <strong class="fs-5">{{ $zone->name }}</strong>
                            <small class="d-block text-muted">{{ $zone->code }} · {{ $zone->type }} · вместимость {{ $zone->capacity }}</small>
                            <small class="d-block text-muted">
                                Удалён: {{ $zone->deleted_at?->format('d.m.Y H:i') ?: '—' }}
                                @if($zone->deletedBy)
                                    · {{ $zone->deletedBy->name }}
                                @endif
                            </small>
                        </div>
                        <form method="post" action="{{ route('admin.pool.zones.store') }}">
                            @csrf
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="zone_id" value="{{ $zone->id }}">
                            <button class="btn btn-success">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить
                            </button>
                        </form>
                    </div>

                    @if($zone->lanesWithTrashed->isNotEmpty())
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted d-block mb-2">Дорожки этого бассейна:</small>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($zone->lanesWithTrashed as $lane)
                                    <span class="badge text-bg-light border p-2">
                                        №{{ $lane->number }} {{ $lane->name }}
                                        @if($lane->trashed())
                                            · в архиве
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5">Удалённых бассейнов нет.</div>
            @endforelse
        </div>
    </div>

    <div class="col-xl-5">
        <div class="admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Удалённые дорожки</h3>
                <span class="badge text-bg-secondary">{{ $archivedLanes->count() }}</span>
            </div>
            <p class="small text-muted">Здесь показаны дорожки, удалённые отдельно. Дорожки, удалённые вместе с бассейном, восстанавливаются вместе с ним.</p>

            @forelse($archivedLanes as $lane)
                <div class="border rounded-4 p-3 mb-3">
                    <strong>{{ $lane->name }} · №{{ $lane->number }}</strong>
                    <small class="d-block text-muted">{{ $lane->zone?->name ?: 'Бассейн недоступен' }} · {{ $lane->length_meters }} м</small>
                    <small class="d-block text-muted mb-3">
                        Удалена: {{ $lane->deleted_at?->format('d.m.Y H:i') ?: '—' }}
                        @if($lane->deletedBy)
                            · {{ $lane->deletedBy->name }}
                        @endif
                    </small>
                    <form method="post" action="{{ route('admin.pool.lanes.store') }}">
                        @csrf
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="lane_id" value="{{ $lane->id }}">
                        <button class="btn btn-sm btn-success">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить дорожку
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-5">Отдельно удалённых дорожек нет.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
