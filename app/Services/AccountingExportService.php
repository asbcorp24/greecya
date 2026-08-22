<?php

namespace App\Services;

use App\Models\AccountingIntegration;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AccountingExportService
{
    public function build(Carbon $from, Carbon $to, ?AccountingIntegration $integration = null): array
    {
        $orders = Order::query()
            ->with(['customer','items.product','payments'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($sub) use ($from, $to) {
                        $sub->where('payment_status', 'refunded')->whereBetween('updated_at', [$from, $to]);
                    });
            })->get();

        $payments = Payment::query()
            ->with('order.customer')
            ->whereIn('status', ['paid','refunded'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('paid_at', [$from, $to])->orWhereBetween('updated_at', [$from, $to]);
            })->get();

        $cash = CashTransaction::query()
            ->with(['customer','order'])
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('occurred_at')->get();

        $customerIds = $orders->pluck('customer_id')->merge($cash->pluck('customer_id'))->filter()->unique()->values();
        $productIds = $orders->flatMap(fn($o) => $o->items->pluck('product_id'))->filter()->unique()->values();
        $customers = Customer::whereIn('id', $customerIds)->get();
        $products = Product::whereIn('id', $productIds)->get();

        $payload = [
            'meta' => [
                'source' => 'greecya',
                'generated_at' => now()->toIso8601String(),
                'period_from' => $from->toIso8601String(),
                'period_to' => $to->toIso8601String(),
                'organization_code' => $integration?->organization_code,
                'format_version' => $integration?->format_version ?: '1.23',
            ],
            'counterparties' => $customers->map(fn(Customer $c) => [
                'id' => 'customer:'.$c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'type' => 'individual',
            ])->values()->all(),
            'nomenclature' => $products->map(fn(Product $p) => [
                'id' => 'product:'.$p->id,
                'sku' => $p->slug,
                'name' => $p->name,
                'kind' => $p->type,
                'unit' => 'шт',
                'price' => (float) $p->price,
            ])->values()->all(),
            'sales' => $orders->where('payment_status','paid')->map(fn(Order $o) => [
                'id' => 'order:'.$o->id,
                'number' => $o->number,
                'date' => optional($o->paid_at)->toIso8601String(),
                'counterparty_id' => 'customer:'.$o->customer_id,
                'total' => (float) $o->total,
                'source' => $o->source,
                'items' => $o->items->map(fn($i) => [
                    'product_id' => $i->product_id ? 'product:'.$i->product_id : null,
                    'name' => $i->name,
                    'quantity' => (float) $i->quantity,
                    'price' => (float) $i->price,
                    'total' => (float) $i->total,
                ])->values()->all(),
            ])->values()->all(),
            'returns' => $orders->where('payment_status','refunded')->map(fn(Order $o) => [
                'id' => 'return:'.$o->id,
                'number' => $o->number,
                'date' => $o->updated_at->toIso8601String(),
                'counterparty_id' => 'customer:'.$o->customer_id,
                'total' => (float) $o->total,
            ])->values()->all(),
            'payments' => $payments->map(fn(Payment $p) => [
                'id' => 'payment:'.$p->id,
                'order_id' => 'order:'.$p->order_id,
                'number' => $p->external_id,
                'date' => optional($p->paid_at ?: $p->updated_at)->toIso8601String(),
                'status' => $p->status,
                'provider' => $p->provider,
                'amount' => (float) $p->amount,
            ])->values()->all(),
            'cash_operations' => $cash->map(fn(CashTransaction $t) => [
                'id' => 'cash:'.$t->id,
                'date' => $t->occurred_at->toIso8601String(),
                'type' => $t->type,
                'method' => $t->method,
                'amount' => (float) $t->amount,
                'customer_id' => $t->customer_id ? 'customer:'.$t->customer_id : null,
                'order_id' => $t->order_id ? 'order:'.$t->order_id : null,
                'description' => $t->description,
            ])->values()->all(),
        ];

        $payload['meta']['counts'] = collect($payload)->except('meta')->map(fn($rows) => count($rows))->all();
        return $payload;
    }

    public function encode(array $payload, string $format): string
    {
        return $format === 'xml' ? $this->toXml($payload) : json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    public function push(AccountingIntegration $integration, array $payload): Response
    {
        if (! $integration->endpoint_url) throw new \RuntimeException('Не указан URL HTTP-сервиса 1С.');

        $format = str_contains($integration->driver, 'xml') ? 'xml' : 'json';
        $body = $this->encode($payload, $format);
        $request = Http::timeout((int)($integration->options['timeout'] ?? 30))
            ->acceptJson()
            ->withHeaders([
                'X-Greecya-Exchange' => 'accounting',
                'X-Greecya-Format-Version' => $integration->format_version ?: '1.23',
                'X-Greecya-Signature' => $integration->token ? hash_hmac('sha256', $body, $integration->token) : '',
            ]);

        if ($integration->username) $request = $request->withBasicAuth($integration->username, (string)$integration->password);
        if ($integration->token) $request = $request->withToken($integration->token);

        return $request->withBody($body, $format === 'xml' ? 'application/xml' : 'application/json')
            ->post($integration->endpoint_url);
    }

    private function toXml(array $payload): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('GreecyaAccountingExchange');
        $xml->writeAttribute('version', (string)($payload['meta']['format_version'] ?? '1.23'));
        $this->writeXml($xml, $payload);
        $xml->endElement();
        $xml->endDocument();
        return $xml->outputMemory();
    }

    private function writeXml(\XMLWriter $xml, array $data): void
    {
        foreach ($data as $key => $value) {
            $name = is_int($key) ? 'item' : preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$key);
            if (is_array($value)) {
                $xml->startElement($name);
                $this->writeXml($xml, $value);
                $xml->endElement();
            } else {
                $xml->writeElement($name, $value === null ? '' : (string)$value);
            }
        }
    }
}
