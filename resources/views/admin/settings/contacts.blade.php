@extends('admin.layout')
@section('title','Контакты и реквизиты') @section('heading','Контакты и реквизиты') @section('eyebrow','Отдельный справочник')
@section('content')
<form method="post" action="{{ route('admin.settings.contacts.update') }}">@csrf @method('patch')
<div class="row g-4">
    <div class="col-xl-9">
        @foreach(['contacts'=>'Телефоны, email и адрес','schedule'=>'Режим работы и карта','social'=>'Социальные сети и мессенджеры','company'=>'Юридические реквизиты'] as $groupKey=>$groupTitle)
            <div class="admin-card p-4 mb-4"><div class="admin-card-header px-0 pt-0"><div><h3>{{ $groupTitle }}</h3><p>Эти значения используются в шапке, футере, сертификатах и SEO-разметке.</p></div></div><div class="row g-3">@include('admin.settings._fields',['groupSettings'=>$settings->get($groupKey,collect())])</div></div>
        @endforeach
    </div>
    <div class="col-xl-3"><div class="admin-card p-4 sticky-xl-top" style="top:100px"><h3>Контакты</h3><p class="text-muted">Телефон автоматически преобразуется в ссылку для звонка. Ссылки на соцсети нужно указывать полностью, начиная с https://.</p><button class="btn btn-primary btn-lg w-100">Сохранить</button></div></div>
</div></form>
@endsection
