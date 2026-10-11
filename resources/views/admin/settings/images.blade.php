@extends('admin.layout')

@section('title','Сжатие изображений')
@section('heading','Сжатие изображений')
@section('eyebrow','Оптимизация сайта')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="admin-card p-4 h-100">
            <div class="text-muted small mb-1">Всего изображений</div>
            <div class="display-6 fw-bold">{{ $stats['count'] }}</div>
            <div class="text-muted mt-2">JPG, JPEG, PNG и WebP в публичном хранилище.</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card p-4 h-100">
            <div class="text-muted small mb-1">Общий размер</div>
            <div class="display-6 fw-bold">{{ $stats['size_formatted'] }}</div>
            <div class="text-muted mt-2">Размер всех найденных картинок до новой оптимизации.</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card p-4 h-100">
            <div class="text-muted small mb-1">Уже обработано</div>
            <div class="display-6 fw-bold">{{ $stats['optimized'] }} / {{ $stats['count'] }}</div>
            <div class="text-muted mt-2">Новых или изменённых: <strong>{{ $stats['pending'] }}</strong>.</div>
        </div>
    </div>
</div>

<div class="admin-card p-4 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-xl-8">
            <h3 class="mb-2"><i class="bi bi-images me-2 text-primary"></i>Оптимизировать изображения всего сайта</h3>
            <p class="text-muted mb-3">
                Пути и имена файлов сохраняются, поэтому ссылки в базе данных не ломаются.
                JPEG и WebP пережимаются с качеством 82%, PNG оптимизируется с сохранением прозрачности.
                Обычные изображения шире 2560 px уменьшаются, для 360° панорам предел — 8192 px.
            </p>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="forceOptimization">
                <label class="form-check-label" for="forceOptimization">
                    Пережимать заново даже уже оптимизированные файлы
                </label>
            </div>
            <div class="small text-muted mt-1">
                Обычно эту галочку включать не нужно: повторное JPEG-сжатие может постепенно ухудшать качество.
            </div>
        </div>
        <div class="col-xl-4">
            <button
                type="button"
                class="btn btn-primary btn-lg w-100 js-optimize-images"
                data-section=""
                data-total="{{ $stats['count'] }}"
                data-label="весь сайт"
                @disabled($stats['count'] === 0)
            >
                <i class="bi bi-file-earmark-zip me-2"></i>Сжать все изображения
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Обновить статистику
            </button>
        </div>
    </div>
</div>

<div id="optimizationProgress" class="admin-card p-4 mb-4 d-none">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="mb-1">Идёт оптимизация</h3>
            <p class="text-muted mb-0" id="optimizationStatus">Подготовка…</p>
        </div>
        <span class="badge rounded-pill text-bg-primary fs-6" id="optimizationPercent">0%</span>
    </div>
    <div class="progress" style="height:14px">
        <div
            id="optimizationBar"
            class="progress-bar progress-bar-striped progress-bar-animated"
            role="progressbar"
            style="width:0%"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="0"
        ></div>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-6 col-lg-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Проверено</small><strong id="progressProcessed">0</strong></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Сжато</small><strong id="progressOptimized">0</strong></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Пропущено</small><strong id="progressSkipped">0</strong></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Экономия</small><strong id="progressSaved">0 Б</strong></div></div>
    </div>
    <div id="optimizationErrors" class="alert alert-warning mt-3 mb-0 d-none"></div>
</div>

<div class="d-flex justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h3 class="mb-1">Разделы хранения</h3>
        <p class="text-muted mb-0">Можно оптимизировать весь сайт или только конкретную папку.</p>
    </div>
</div>

<div class="row g-3">
    @forelse($stats['sections'] as $section)
        <div class="col-md-6 col-xl-4">
            <div class="admin-card p-4 h-100">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">{{ $section['label'] }}</h4>
                        <div class="text-muted small text-break">storage/app/public/{{ $section['path'] }}/</div>
                    </div>
                    <span class="badge rounded-pill text-bg-light border align-self-start">{{ $section['count'] }} шт.</span>
                </div>

                <div class="d-flex gap-4 mt-4 mb-3">
                    <div>
                        <small class="text-muted d-block">Размер</small>
                        <strong>{{ $section['size_formatted'] }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Обработано</small>
                        <strong>{{ $section['optimized'] }} / {{ $section['count'] }}</strong>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-outline-primary w-100 js-optimize-images"
                    data-section="{{ $section['key'] }}"
                    data-total="{{ $section['count'] }}"
                    data-label="{{ $section['label'] }}"
                >
                    <i class="bi bi-file-earmark-zip me-1"></i>Сжать этот раздел
                </button>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="admin-card p-5 text-center text-muted">
                <i class="bi bi-images fs-1 d-block mb-3"></i>
                В публичном хранилище пока нет JPG, PNG или WebP изображений.
            </div>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
(() => {
    const endpoint = @json(route('admin.settings.images.compress'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const buttons = Array.from(document.querySelectorAll('.js-optimize-images'));
    const progress = document.getElementById('optimizationProgress');
    const bar = document.getElementById('optimizationBar');
    const percent = document.getElementById('optimizationPercent');
    const status = document.getElementById('optimizationStatus');
    const processedEl = document.getElementById('progressProcessed');
    const optimizedEl = document.getElementById('progressOptimized');
    const skippedEl = document.getElementById('progressSkipped');
    const savedEl = document.getElementById('progressSaved');
    const errorsEl = document.getElementById('optimizationErrors');
    const forceEl = document.getElementById('forceOptimization');

    const formatBytes = bytes => {
        if (bytes < 1024) return bytes + ' Б';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1).replace('.', ',') + ' КБ';
        if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' МБ';
        return (bytes / (1024 * 1024 * 1024)).toFixed(2).replace('.', ',') + ' ГБ';
    };

    const setButtonsDisabled = disabled => {
        buttons.forEach(button => button.disabled = disabled);
        forceEl.disabled = disabled;
    };

    const run = async (section, expectedTotal, label) => {
        if (! expectedTotal) return;

        if (! confirm('Запустить сжатие изображений: ' + label + '? Файлы будут заменяться оптимизированными версиями с сохранением путей.')) {
            return;
        }

        setButtonsDisabled(true);
        progress.classList.remove('d-none');
        errorsEl.classList.add('d-none');
        errorsEl.textContent = '';

        let offset = 0;
        let total = expectedTotal;
        let checked = 0;
        let optimized = 0;
        let skipped = 0;
        let failed = 0;
        let saved = 0;

        const updateUi = () => {
            const value = total > 0 ? Math.min(100, Math.round((checked / total) * 100)) : 100;
            bar.style.width = value + '%';
            bar.setAttribute('aria-valuenow', String(value));
            percent.textContent = value + '%';
            processedEl.textContent = checked + ' / ' + total;
            optimizedEl.textContent = optimized;
            skippedEl.textContent = skipped;
            savedEl.textContent = formatBytes(saved);
            status.textContent = value >= 100
                ? 'Обработка завершена.'
                : 'Обрабатывается: ' + label + '…';
        };

        updateUi();

        try {
            while (offset < total) {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        offset,
                        section: section || null,
                        force: !!forceEl.checked,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw new Error(data.message || 'Сервер вернул ошибку при обработке изображений.');
                }

                total = data.total;
                offset = data.next_offset;
                checked = offset;
                optimized += data.optimized || 0;
                skipped += data.skipped || 0;
                failed += data.failed || 0;
                saved += data.saved_bytes || 0;

                updateUi();

                if (data.done) break;
            }

            bar.classList.remove('progress-bar-animated');
            status.textContent = 'Готово: ' + label + '. Обновите статистику, чтобы увидеть новый общий размер.';

            if (failed > 0) {
                errorsEl.classList.remove('d-none');
                errorsEl.textContent = 'Не удалось обработать файлов: ' + failed + '. Остальные изображения обработаны.';
            }
        } catch (error) {
            status.textContent = 'Сжатие остановлено из-за ошибки.';
            errorsEl.classList.remove('d-none');
            errorsEl.textContent = error.message || String(error);
        } finally {
            setButtonsDisabled(false);
        }
    };

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            run(
                button.dataset.section || '',
                Number(button.dataset.total || 0),
                button.dataset.label || 'выбранный раздел'
            );
        });
    });
})();
</script>
@endpush
