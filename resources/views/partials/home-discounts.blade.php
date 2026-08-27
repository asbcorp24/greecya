@if($discounts->isNotEmpty())
<section class="section-padding home-discounts" id="discounts">
    <div class="container">
        <div class="row align-items-end g-4 mb-5">
            <div class="col-lg-7">
                <div class="eyebrow discount-eyebrow"><i class="bi bi-stars"></i> Динамическое ценообразование</div>
                <h2 class="section-title text-white mb-3">Скидки и <span>выгодное время</span></h2>
                <p class="discounts-intro mb-0">Выбирайте время и формат выгоднее. Итоговая цена рассчитывается автоматически при записи с учётом действующих условий.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <a href="{{ route('booking.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Проверить цену на время <i class="bi bi-arrow-right ms-2"></i></a>
            </div>
        </div>

        <div class="row g-4">
            @foreach($discounts as $discount)
                <div class="col-md-6 col-xl-4">
                    <article class="discount-card h-100">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                            <div>
                                <span class="discount-target">{{ $discount['target'] }}</span>
                                @if($discount['upcoming'])<span class="discount-status">Скоро</span>@endif
                            </div>
                            <span class="discount-value">{{ $discount['badge'] }}</span>
                        </div>

                        <h3>{{ $discount['name'] }}</h3>

                        @if($discount['base_price'] !== null && $discount['discounted_price'] !== null)
                            <div class="discount-price-row">
                                <span>{{ number_format($discount['base_price'], 0, ',', ' ') }} ₽</span>
                                <strong>{{ number_format($discount['discounted_price'], 0, ',', ' ') }} ₽</strong>
                            </div>
                        @endif

                        <div class="discount-conditions mt-3">
                            @foreach($discount['conditions'] as $condition)
                                <span><i class="bi bi-check2-circle"></i>{{ $condition }}</span>
                            @endforeach
                        </div>

                        <a href="{{ $discount['url'] }}" class="btn btn-outline-light rounded-pill w-100 mt-auto">{{ $discount['cta'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                    </article>
                </div>
            @endforeach
        </div>

        <p class="discount-note mt-4 mb-0"><i class="bi bi-info-circle me-1"></i> Если одновременно подходят несколько правил, окончательная цена определяется системой при выборе конкретного времени и клиента.</p>
    </div>
</section>
@endif
