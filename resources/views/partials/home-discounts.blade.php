@if($discounts->isNotEmpty())
@push('styles')
<style>
.home-discounts{position:relative;overflow:hidden;background:linear-gradient(135deg,#032f43 0%,#086a95 58%,#0796a9 100%);color:#fff}.home-discounts:before{content:"%";position:absolute;right:-1rem;top:-6rem;font:700 28rem/1 Manrope,sans-serif;color:rgba(255,255,255,.035);pointer-events:none}.home-discounts .container{position:relative;z-index:1}.discount-eyebrow{color:#bfeff4}.discounts-intro{max-width:760px;color:rgba(255,255,255,.76);font-size:1.08rem;line-height:1.7}.discount-card{display:flex;flex-direction:column;padding:1.55rem;border:1px solid rgba(255,255,255,.16);border-radius:1.6rem;background:rgba(255,255,255,.105);backdrop-filter:blur(12px);box-shadow:0 20px 55px rgba(0,0,0,.12);transition:.22s}.discount-card:hover{transform:translateY(-4px);background:rgba(255,255,255,.14)}.discount-target{display:block;color:#bfeff4;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.discount-status{display:inline-block;margin-top:.4rem;padding:.2rem .55rem;border-radius:999px;background:#fff3cd;color:#745c00;font-size:.72rem;font-weight:800}.discount-value{flex:0 0 auto;padding:.6rem .85rem;border-radius:1rem;background:#fff;color:#086a95;font-size:1.25rem;font-weight:900;line-height:1}.discount-card h3{font-family:'Prata',serif;font-size:1.55rem;line-height:1.25;margin:0 0 .8rem;color:#fff}.discount-price-row{display:flex;align-items:baseline;gap:.75rem;margin:.15rem 0 .3rem}.discount-price-row span{color:rgba(255,255,255,.55);text-decoration:line-through;font-size:1rem}.discount-price-row strong{font-size:1.55rem;color:#fff}.discount-conditions{display:flex;flex-direction:column;gap:.48rem;margin-bottom:1.35rem}.discount-conditions span{display:flex;align-items:flex-start;gap:.45rem;color:rgba(255,255,255,.82);font-size:.92rem}.discount-conditions i{color:#78e2d3;margin-top:.12rem}.discount-note{color:rgba(255,255,255,.6);font-size:.86rem}@media(max-width:767px){.home-discounts:before{font-size:18rem;right:-3rem}.discount-card{padding:1.3rem}}
</style>
@endpush
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
