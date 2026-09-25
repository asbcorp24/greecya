@if($panoramas->isNotEmpty() && $featuredPanorama)
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
<link rel="stylesheet" href="{{ asset('css/panoramas.css') }}?v={{ file_exists(public_path('css/panoramas.css')) ? filemtime(public_path('css/panoramas.css')) : 1 }}">
@endpush

@php
    $panoramaScenes = $panoramas->mapWithKeys(function ($panorama) {
        return ['panorama-'.$panorama->id => [
            'type' => 'equirectangular',
            'panorama' => Storage::url($panorama->image_path),
            'title' => $panorama->title,
            'autoLoad' => true,
            'showControls' => true,
            'showFullscreenCtrl' => true,
            'mouseZoom' => true,
            'draggable' => true,
        ]];
    });
@endphp

<section class="section-padding panorama-section" id="panoramas">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="panorama-copy">
                    <div class="eyebrow eyebrow-blue"><i class="bi bi-badge-3d"></i> Виртуальная экскурсия</div>
                    <h2 class="section-title">Посмотрите комплекс <span>в 360°</span></h2>
                    <p class="section-text">{{ $featuredPanorama->description ?: 'Осмотритесь вокруг прямо на сайте: зажмите изображение мышью и вращайте панораму в любую сторону.' }}</p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#panoramasModal">
                            Другие панорамы <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                        <span class="d-inline-flex align-items-center gap-2 text-muted"><i class="bi bi-mouse"></i> Вращайте мышкой</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="panorama-viewer-shell">
                    <div class="panorama-viewer-badge"><i class="bi bi-badge-3d me-1"></i> {{ $featuredPanorama->title }}</div>
                    <div id="homePanoramaViewer" class="panorama-viewer" aria-label="360° панорама {{ $featuredPanorama->title }}"></div>
                    <div class="panorama-viewer-hint"><i class="bi bi-arrows-move me-1"></i> Зажмите и тяните мышкой, чтобы осмотреться</div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade panorama-modal" id="panoramasModal" tabindex="-1" aria-labelledby="panoramasModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 px-4 pt-4">
                <div>
                    <div class="eyebrow eyebrow-blue mb-1">360° экскурсия</div>
                    <h2 class="modal-title h3 mb-0" id="panoramasModalTitle">Другие панорамы</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <div class="row g-4">
                    <div class="col-lg-9">
                        <div id="panoramaModalViewer" class="panorama-modal-viewer rounded-4 overflow-hidden"></div>
                        <div class="panorama-modal-info mt-3">
                            <h3 class="h5 mb-1" id="panoramaModalName">{{ $featuredPanorama->title }}</h3>
                            <p class="text-muted mb-0" id="panoramaModalDescription">{{ $featuredPanorama->description }}</p>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="panorama-list d-grid gap-2">
                            @foreach($panoramas as $panorama)
                                <button
                                    type="button"
                                    class="panorama-choice {{ $panorama->id === $featuredPanorama->id ? 'active' : '' }}"
                                    data-scene="panorama-{{ $panorama->id }}"
                                    data-title="{{ $panorama->title }}"
                                    data-description="{{ $panorama->description }}"
                                >
                                    <img src="{{ Storage::url($panorama->image_path) }}" alt="">
                                    <span>
                                        <strong>{{ $panorama->title }}</strong>
                                        <small>{{ Str::limit($panorama->description ?: '360° панорама комплекса', 60) }}</small>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<script>
(() => {
    if (typeof pannellum === 'undefined') return;

    const scenes = @json($panoramaScenes);
    const featuredScene = @json('panorama-'.$featuredPanorama->id);

    pannellum.viewer('homePanoramaViewer', {
        type: 'equirectangular',
        panorama: @json(Storage::url($featuredPanorama->image_path)),
        autoLoad: true,
        showControls: true,
        showFullscreenCtrl: true,
        mouseZoom: true,
        draggable: true,
        hfov: 100,
        minHfov: 50,
        maxHfov: 120
    });

    const modalElement = document.getElementById('panoramasModal');
    const modalName = document.getElementById('panoramaModalName');
    const modalDescription = document.getElementById('panoramaModalDescription');
    const choices = Array.from(document.querySelectorAll('.panorama-choice'));
    let modalViewer = null;
    let currentScene = featuredScene;

    function selectScene(sceneId, title, description) {
        currentScene = sceneId;
        choices.forEach(button => button.classList.toggle('active', button.dataset.scene === sceneId));
        modalName.textContent = title || '';
        modalDescription.textContent = description || '';

        if (modalViewer) {
            modalViewer.loadScene(sceneId);
        }
    }

    modalElement.addEventListener('shown.bs.modal', () => {
        if (!modalViewer) {
            modalViewer = pannellum.viewer('panoramaModalViewer', {
                default: {
                    firstScene: currentScene,
                    autoLoad: true,
                    sceneFadeDuration: 450,
                    showControls: true,
                    showFullscreenCtrl: true,
                    mouseZoom: true,
                    draggable: true,
                    hfov: 100
                },
                scenes
            });
        } else {
            modalViewer.resize();
        }
    });

    choices.forEach(button => {
        button.addEventListener('click', () => {
            selectScene(button.dataset.scene, button.dataset.title, button.dataset.description);
        });
    });
})();
</script>
@endpush
@endif
