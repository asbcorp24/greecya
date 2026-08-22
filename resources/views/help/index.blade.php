@extends($layout)

@section('title', 'Справка — '.$roleHelp['label'])
@section('heading', 'Справочный центр')
@section('eyebrow', 'Инструкции, примеры и типовые ошибки')
@section('workspace_name', 'Справка · '.$roleHelp['label'])

@push('styles')
<style>
.help-page{max-width:1320px;margin:0 auto}.help-hero{border-radius:28px;padding:28px;background:linear-gradient(135deg,#102a43,#0b5ed7);color:#fff;box-shadow:0 20px 50px rgba(13,71,161,.16)}.help-hero .role-icon{width:64px;height:64px;border-radius:20px;display:grid;place-items:center;background:rgba(255,255,255,.14);font-size:30px}.help-role-tabs{display:flex;gap:8px;overflow:auto;padding-bottom:6px}.help-role-tabs a{white-space:nowrap;border-radius:999px}.help-card{background:#fff;border:1px solid #e7ecf3;border-radius:22px;padding:22px;height:100%;box-shadow:0 10px 28px rgba(32,51,73,.05)}.help-card h2,.help-card h3{margin-top:0}.help-step{display:flex;gap:14px;padding:12px 0;border-bottom:1px solid #edf1f5}.help-step:last-child{border-bottom:0}.help-step .num{flex:0 0 34px;width:34px;height:34px;border-radius:12px;background:#eaf2ff;color:#0b5ed7;display:grid;place-items:center;font-weight:800}.help-workflow{border:1px solid #e7ecf3;border-radius:18px;overflow:hidden;margin-bottom:14px}.help-workflow summary{cursor:pointer;padding:18px 20px;font-weight:800;background:#f8fafc;list-style:none}.help-workflow summary::-webkit-details-marker{display:none}.help-workflow-body{padding:20px}.help-example{background:#eef7ff;border-left:4px solid #0b5ed7;border-radius:12px;padding:14px 16px}.help-error{background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:14px}.help-error strong{color:#9a3412}.help-danger-list li{margin-bottom:8px}.help-check li{margin-bottom:10px}.help-search{position:sticky;top:12px;z-index:5;background:rgba(244,247,251,.95);backdrop-filter:blur(10px);border-radius:18px;padding:10px}.help-empty{display:none}.help-toc a{text-decoration:none}.help-common-code{min-width:74px}.help-hidden{display:none!important}@media print{.help-no-print,.admin-sidebar,.workspace-header,.site-navbar,.site-footer,.admin-header{display:none!important}.admin-main,.admin-content,.workspace-wrap{margin:0!important;padding:0!important}.help-page{max-width:none}.help-workflow{break-inside:avoid}.help-workflow[open] .help-workflow-body{display:block}.help-card,.help-hero{box-shadow:none}}
</style>
@endpush

@section('content')
<div class="help-page">
    <div class="help-hero mb-4 help-searchable">
        <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
            <div class="d-flex gap-3 align-items-center">
                <div class="role-icon"><i class="bi {{ $roleHelp['icon'] }}"></i></div>
                <div>
                    <div class="text-white-50 small text-uppercase fw-bold">Роль</div>
                    <h1 class="h2 mb-2">{{ $roleHelp['label'] }}</h1>
                    <p class="mb-0 text-white-50">{{ $roleHelp['summary'] }}</p>
                </div>
            </div>
            <div class="d-flex gap-2 help-no-print">
                @if(!empty($roleHelp['home']) && $canOpenRoleLinks && app('router')->has($roleHelp['home']))
                    <a class="btn btn-light rounded-pill" href="{{ route($roleHelp['home']) }}"><i class="bi bi-box-arrow-up-right me-1"></i> Рабочий раздел</a>
                @endif
                <button class="btn btn-outline-light rounded-pill" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i> Печать</button>
            </div>
        </div>
    </div>

    @if($canBrowseAll)
        <div class="help-card mb-4 help-no-print">
            <div class="small text-muted fw-bold text-uppercase mb-2">Справка по ролям</div>
            <div class="help-role-tabs">
                @foreach($roles as $roleCode => $role)
                    <a class="btn {{ $selectedRole === $roleCode ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm" href="{{ route('help.index', ['role' => $roleCode]) }}">
                        <i class="bi {{ $role['icon'] }} me-1"></i>{{ $role['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="help-search mb-4 help-no-print">
        <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input id="helpSearch" class="form-control border-start-0" autocomplete="off" placeholder="Найти: возврат, QR, заморозка, 403, касса, 1С...">
            <button id="helpSearchClear" class="btn btn-outline-secondary" type="button">Сбросить</button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="help-card help-searchable">
                <h2 class="h4"><i class="bi bi-bullseye text-primary me-2"></i>Что входит в работу роли</h2>
                <ul class="mb-0 mt-3">
                    @foreach($roleHelp['responsibilities'] as $item)
                        <li class="mb-2">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="help-card help-toc help-no-print">
                <h2 class="h5">Содержание</h2>
                <div class="d-grid gap-2 mt-3">
                    <a href="#quick-start"><i class="bi bi-lightning-charge me-2"></i>Быстрый старт</a>
                    <a href="#workflows"><i class="bi bi-diagram-3 me-2"></i>Рабочие сценарии</a>
                    <a href="#mistakes"><i class="bi bi-exclamation-triangle me-2"></i>Частые ошибки</a>
                    <a href="#common-errors"><i class="bi bi-bug me-2"></i>Системные ошибки</a>
                    <a href="#checklist"><i class="bi bi-check2-square me-2"></i>Чек-лист</a>
                </div>
            </div>
        </div>
    </div>

    <section id="quick-start" class="help-card mb-4 help-searchable">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-lightning-charge-fill text-warning fs-3"></i>
            <div><div class="small text-muted text-uppercase fw-bold">Начало работы</div><h2 class="h4 mb-0">Быстрый старт</h2></div>
        </div>
        @foreach($roleHelp['quick_start'] as $index => $step)
            <div class="help-step">
                <span class="num">{{ $index + 1 }}</span>
                <div>{{ $step }}</div>
            </div>
        @endforeach
    </section>

    <section id="workflows" class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3 help-searchable">
            <i class="bi bi-diagram-3 text-primary fs-3"></i>
            <div><div class="small text-muted text-uppercase fw-bold">Практика</div><h2 class="h4 mb-0">Рабочие сценарии с примерами</h2></div>
        </div>

        @foreach($roleHelp['workflows'] as $workflowIndex => $workflow)
            <details class="help-workflow help-searchable" @if($workflowIndex === 0) open @endif>
                <summary class="d-flex justify-content-between align-items-center gap-3">
                    <span>{{ $workflowIndex + 1 }}. {{ $workflow['title'] }}</span>
                    <i class="bi bi-chevron-down"></i>
                </summary>
                <div class="help-workflow-body">
                    @if(!empty($workflow['route']) && $canOpenRoleLinks && app('router')->has($workflow['route']))
                        <div class="mb-3 help-no-print">
                            <a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route($workflow['route']) }}"><i class="bi bi-box-arrow-up-right me-1"></i>Открыть соответствующий раздел</a>
                        </div>
                    @endif

                    <h3 class="h6 text-uppercase text-muted">Пошагово</h3>
                    <ol class="mb-4">
                        @foreach($workflow['steps'] as $step)
                            <li class="mb-2">{{ $step }}</li>
                        @endforeach
                    </ol>

                    @if(!empty($workflow['example']))
                        <div class="help-example mb-4">
                            <strong><i class="bi bi-lightbulb me-1"></i>Пример</strong>
                            <div class="mt-1">{{ $workflow['example'] }}</div>
                        </div>
                    @endif

                    @if(!empty($workflow['errors']))
                        <h3 class="h6 text-uppercase text-muted">Если что-то пошло не так</h3>
                        <div class="row g-3">
                            @foreach($workflow['errors'] as $error)
                                <div class="col-lg-6">
                                    <div class="help-error h-100">
                                        <strong>{{ $error['symptom'] }}</strong>
                                        <div class="small mt-2"><b>Почему:</b> {{ $error['cause'] }}</div>
                                        <div class="small mt-1"><b>Что делать:</b> {{ $error['fix'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </details>
        @endforeach
    </section>

    <section id="mistakes" class="help-card mb-4 help-searchable">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-octagon text-danger fs-3"></i>
            <div><div class="small text-muted text-uppercase fw-bold">Важно</div><h2 class="h4 mb-0">Типичные ошибки роли</h2></div>
        </div>
        <ul class="help-danger-list mb-0">
            @foreach($roleHelp['mistakes'] as $mistake)
                <li>{{ $mistake }}</li>
            @endforeach
        </ul>
    </section>

    <section id="common-errors" class="help-card mb-4 help-searchable">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-bug text-warning fs-3"></i>
            <div><div class="small text-muted text-uppercase fw-bold">Для всех ролей</div><h2 class="h4 mb-0">Общие системные ошибки</h2></div>
        </div>
        <p>{{ $common['intro'] }}</p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Код</th><th>Что означает</th><th>Почему</th><th>Что делать</th></tr></thead>
                <tbody>
                @foreach($common['errors'] as $error)
                    <tr>
                        <td class="help-common-code"><span class="badge text-bg-light border">{{ $error['code'] }}</span></td>
                        <td><strong>{{ $error['title'] }}</strong></td>
                        <td>{{ $error['why'] }}</td>
                        <td>{{ $error['fix'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <h3 class="h6 mt-4">Общие правила безопасной работы</h3>
        <ul class="mb-0">
            @foreach($common['rules'] as $rule)
                <li class="mb-2">{{ $rule }}</li>
            @endforeach
        </ul>
    </section>

    <section id="checklist" class="help-card mb-4 help-searchable">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-check2-square text-success fs-3"></i>
            <div><div class="small text-muted text-uppercase fw-bold">Контроль</div><h2 class="h4 mb-0">Чек-лист роли</h2></div>
        </div>
        <ul class="list-unstyled help-check mb-0">
            @foreach($roleHelp['checklist'] as $item)
                <li class="d-flex gap-2"><i class="bi bi-square text-muted"></i><span>{{ $item }}</span></li>
            @endforeach
        </ul>
    </section>

    <div id="helpEmpty" class="help-card help-empty text-center py-5">
        <i class="bi bi-search fs-1 text-muted"></i>
        <h2 class="h5 mt-3">Ничего не найдено</h2>
        <p class="text-muted mb-0">Попробуйте другой запрос: например «оплата», «справка», «QR», «403» или «абонемент».</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('helpSearch');
    const clear = document.getElementById('helpSearchClear');
    const empty = document.getElementById('helpEmpty');
    const items = Array.from(document.querySelectorAll('.help-searchable'));

    const normalize = value => (value || '').toLocaleLowerCase('ru-RU').replace(/ё/g, 'е').trim();

    const apply = () => {
        const query = normalize(input?.value);
        let visible = 0;
        items.forEach(item => {
            const match = !query || normalize(item.textContent).includes(query);
            item.classList.toggle('help-hidden', !match);
            if (match) visible++;
            if (match && query && item.tagName === 'DETAILS') item.open = true;
        });
        if (empty) empty.style.display = visible ? 'none' : 'block';
    };

    input?.addEventListener('input', apply);
    clear?.addEventListener('click', () => {
        if (input) input.value = '';
        apply();
        input?.focus();
    });
})();
</script>
@endpush
