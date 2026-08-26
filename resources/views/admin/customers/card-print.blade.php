<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Карта клиента · {{ $customer->name }}</title>
    <style>
        @page { size: 85.6mm 54mm; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef2f4; font-family: Arial, Helvetica, sans-serif; }
        body { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; }
        .toolbar { display: flex; gap: 8px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 8px; padding: 10px 16px; font-size: 14px; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #0d6efd; color: white; }
        .toolbar a { background: white; color: #222; border: 1px solid #ccd2d8; }
        .client-card {
            width: 85.6mm;
            height: 54mm;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #072d42 0%, #087fa1 58%, #2fc7d7 100%);
            color: white;
            border-radius: 3.2mm;
            box-shadow: 0 14px 38px rgba(0,0,0,.18);
            padding: 4.2mm;
            display: grid;
            grid-template-columns: 18mm 1fr 24mm;
            gap: 3mm;
            align-items: center;
        }
        .client-card::after {
            content: '';
            position: absolute;
            width: 42mm;
            height: 42mm;
            border-radius: 50%;
            right: -18mm;
            bottom: -24mm;
            border: 8mm solid rgba(255,255,255,.08);
        }
        .photo {
            width: 18mm;
            height: 23mm;
            border-radius: 2.2mm;
            overflow: hidden;
            border: .6mm solid rgba(255,255,255,.7);
            background: rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9mm;
            font-weight: 700;
        }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .brand { font-size: 3.2mm; font-weight: 800; letter-spacing: .35mm; text-transform: uppercase; margin-bottom: 2mm; }
        .name { font-size: 4.2mm; line-height: 1.1; font-weight: 800; margin-bottom: 1.7mm; max-height: 10mm; overflow: hidden; }
        .meta { font-size: 2.5mm; line-height: 1.45; opacity: .94; }
        .meta strong { font-weight: 700; }
        .qr-wrap { align-self: center; justify-self: end; width: 24mm; text-align: center; position: relative; z-index: 2; }
        .qr-box { width: 23mm; height: 23mm; background: #fff; border-radius: 1.4mm; padding: 1mm; margin: 0 auto 1mm; }
        #printQr { width: 21mm; height: 21mm; }
        #printQr img, #printQr canvas { width: 21mm !important; height: 21mm !important; }
        .code { font-size: 1.9mm; overflow-wrap: anywhere; line-height: 1.15; }
        .customer-no { position: absolute; left: 4.2mm; bottom: 2.8mm; font-size: 2.1mm; opacity: .82; }
        @media print {
            html, body { width: 85.6mm; height: 54mm; background: white; display: block; }
            .toolbar { display: none !important; }
            .client-card { box-shadow: none; border-radius: 0; margin: 0; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Распечатать</button>
    <a href="{{ route('admin.customers.show', $customer) }}">Вернуться к клиенту</a>
</div>

<div class="client-card">
    <div class="photo">
        @if($customer->photo_path)
            <img src="{{ asset('storage/'.$customer->photo_path) }}" alt="{{ $customer->name }}">
        @else
            {{ mb_substr($customer->name, 0, 1) }}
        @endif
    </div>

    <div>
        <div class="brand">Греция · SPA & бассейн</div>
        <div class="name">{{ $customer->name }}</div>
        <div class="meta">
            @if($membership)
                <div><strong>Абонемент:</strong> {{ $membership->plan?->name ?: $membership->number }}</div>
                <div><strong>до:</strong> {{ $membership->ends_on?->format('d.m.Y') ?: 'без срока' }}</div>
            @else
                <div><strong>Статус:</strong> клиент комплекса</div>
                <div><strong>QR-карта:</strong> активна</div>
            @endif
        </div>
    </div>

    <div class="qr-wrap">
        <div class="qr-box"><div id="printQr" data-code="{{ $card->code }}"></div></div>
        <div class="code">{{ $card->code }}</div>
    </div>

    <div class="customer-no">Клиент № {{ str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT) }}</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var node = document.getElementById('printQr');
        if (node && window.QRCode) {
            new QRCode(node, {
                text: node.dataset.code,
                width: 210,
                height: 210,
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
</script>
</body>
</html>
