@extends('admin.layout')
@section('title','Настройки сайта') @section('heading','Настройки сайта') @section('eyebrow','Бренд и промо')
@section('content')
<form method="post" action="{{ route('admin.settings.general.update') }}" enctype="multipart/form-data">@csrf @method('patch')
<div class="row g-4">
    <div class="col-xl-8">
        @foreach(['site'=>'Основные данные сайта','promo'=>'Промо-панель'] as $groupKey=>$groupTitle)
            <div class="admin-card p-4 mb-4"><div class="admin-card-header px-0 pt-0"><div><h3>{{ $groupTitle }}</h3><p>Изменения сразу применяются на публичном сайте.</p></div></div><div class="row g-3">@include('admin.settings._fields',['groupSettings'=>$settings->get($groupKey,collect())])</div></div>
        @endforeach
    </div>
    <div class="col-xl-4"><div class="admin-card p-4 sticky-xl-top" style="top:100px"><h3>Сохранение</h3><p class="text-muted">После сохранения кеш настроек очищается автоматически. Для логотипа и favicon используйте PNG, SVG или WebP.</p><button class="btn btn-primary btn-lg w-100">Сохранить настройки</button><a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary w-100 mt-2">Открыть сайт</a></div></div>
</div></form>
@endsection
