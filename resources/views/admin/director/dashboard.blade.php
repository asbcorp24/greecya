@extends('admin.layout')
@section('title','Панель директора')
@section('heading','Директорский dashboard')
@section('eyebrow','Финансы, загрузка и эффективность')
@section('content')
<div class="row g-3 mb-4">
@foreach([
['Выручка сегодня',$todayRevenue,'₽','bi-cash-stack'],['Выручка за месяц',$monthRevenue,'₽','bi-graph-up-arrow'],['Средний чек',$avgCheck,'₽','bi-receipt'],['Прогноз месяца',$forecastRevenue,'₽','bi-stars'],
['Продажи абонементов',$membershipSales,'','bi-person-vcard'],['Продления',$renewals,'','bi-arrow-repeat'],['Посетители сегодня',$visitorsToday,'','bi-people'],['Отмены за месяц',$cancellations,'','bi-calendar-x'],
['Задолженность',$debt,'₽','bi-exclamation-circle'],['Фонд зарплаты',$payrollFund,'₽','bi-wallet2'],['Конверсия лидов',$leadConversion,'%','bi-funnel'],['Оплаченных заказов',$monthOrders,'','bi-check2-circle']
] as [$label,$value,$suffix,$icon])
<div class="col-6 col-lg-3"><div class="admin-card p-3 h-100"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">{{ $label }}</small><div class="fs-4 fw-bold mt-1">{{ is_numeric($value) ? number_format((float)$value, $suffix==='%'?1:0, ',', ' ') : $value }} {{ $suffix }}</div></div><i class="bi {{ $icon }} fs-3 text-primary"></i></div></div></div>
@endforeach
</div>
<div class="row g-4 mb-4">
<div class="col-xl-8"><div class="admin-card p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-0">Выручка за 30 дней</h3><small class="text-muted">Оплаченные заказы</small></div><span class="badge text-bg-light">Прогноз: {{ number_format($forecastRevenue,0,',',' ') }} ₽</span></div><canvas id="revenueChart" height="95"></canvas></div></div>
<div class="col-xl-4"><div class="admin-card p-4"><h3 class="mb-0">Загрузка по часам</h3><small class="text-muted">Средняя загрузка дорожек/сеансов за текущий месяц</small><canvas id="loadChart" height="205"></canvas></div></div>
</div>
<div class="row g-4"><div class="col-md-6"><div class="admin-card p-4"><h3>Продажи и CRM</h3><div class="d-flex justify-content-between border-bottom py-2"><span>Лидов за месяц</span><strong>{{ $leadsTotal }}</strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>Успешных лидов</span><strong>{{ $leadsWon }}</strong></div><div class="d-flex justify-content-between py-2"><span>Конверсия</span><strong>{{ number_format($leadConversion,1,',',' ') }}%</strong></div></div></div><div class="col-md-6"><div class="admin-card p-4"><h3>Контроль показателей</h3><p class="text-muted mb-2">Панель считается в реальном времени по заказам, посещениям, абонементам, начислениям зарплаты, лидам и расписанию.</p><a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">Открыть подробные отчёты</a></div></div></div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revenueChart'),{type:'line',data:{labels:@json($dailyRevenue->pluck('date')),datasets:[{label:'₽',data:@json($dailyRevenue->pluck('value')),tension:.3,fill:true}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('loadChart'),{type:'bar',data:{labels:@json($hourlyLoad->keys()->values()),datasets:[{label:'Загрузка, %',data:@json($hourlyLoad->values())}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}}}});
</script>
@endpush
