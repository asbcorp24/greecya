@php
    $qrCard = $customer->accessCards
        ->filter(fn ($card) => $card->type === 'qr' && $card->status === 'active' && (! $card->expires_at || $card->expires_at->isFuture()))
        ->sortByDesc('issued_at')
        ->first();
@endphp

<div class="admin-card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="small text-muted text-uppercase fw-bold">Идентификация и СКУД</div>
            <h3 class="h5 mb-1">Карта клиента / QR</h3>
            <p class="text-muted mb-0">QR содержит код карты доступа и может быть распечатан на пластиковой карте клиента.</p>
        </div>

        @if($qrCard)
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="{{ route('admin.customers.card.print', $customer) }}" target="_blank">
                    <i class="bi bi-printer me-1"></i>Распечатать карту
                </a>
                <form method="post" action="{{ route('admin.customers.card.reissue', $customer) }}" onsubmit="return confirm('Перевыпустить QR-карту? Старая QR-карта перестанет работать.');">
                    @csrf
                    <button class="btn btn-outline-danger" type="submit">
                        <i class="bi bi-arrow-repeat me-1"></i>Перевыпустить
                    </button>
                </form>
            </div>
        @else
            <form method="post" action="{{ route('admin.customers.card.issue', $customer) }}">
                @csrf
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-qr-code me-1"></i>Выдать QR-карту
                </button>
            </form>
        @endif
    </div>

    @if($qrCard)
        <div class="row g-4 align-items-center mt-1">
            <div class="col-auto">
                <div class="border rounded-3 bg-white p-2" style="width:156px;height:156px">
                    <div id="customerQrPreview" data-code="{{ $qrCard->code }}" style="width:140px;height:140px"></div>
                </div>
            </div>
            <div class="col">
                <dl class="row mb-0 small">
                    <dt class="col-sm-3">Код карты</dt>
                    <dd class="col-sm-9"><code class="user-select-all">{{ $qrCard->code }}</code></dd>
                    <dt class="col-sm-3">Статус</dt>
                    <dd class="col-sm-9"><span class="badge text-bg-success">Активна</span></dd>
                    <dt class="col-sm-3">Выдана</dt>
                    <dd class="col-sm-9">{{ $qrCard->issued_at?->format('d.m.Y H:i') ?: '—' }}</dd>
                    <dt class="col-sm-3">Действует до</dt>
                    <dd class="col-sm-9">{{ $qrCard->expires_at?->format('d.m.Y H:i') ?: 'без ограничения' }}</dd>
                </dl>
                <div class="alert alert-light border mt-3 mb-0 small">
                    При сканировании СКУД получает строку <strong>{{ $qrCard->code }}</strong> и находит по ней клиента. Не заменяйте QR кодом заказа или билета.
                </div>
            </div>
        </div>

        @once
            <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        @endonce
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var node = document.getElementById('customerQrPreview');
                if (node && window.QRCode) {
                    node.innerHTML = '';
                    new QRCode(node, {
                        text: node.dataset.code,
                        width: 140,
                        height: 140,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            });
        </script>
    @endif
</div>
