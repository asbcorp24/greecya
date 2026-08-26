<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Membership;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PointOfSaleService
{
    public function __construct(private DynamicPricingService $pricing)
    {
    }

    public function sell(array $data, User $seller, string $source): Order
    {
        return DB::transaction(function () use ($data, $seller, $source) {
            $shift = CashShift::query()
                ->with('register')
                ->whereKey($data['cash_shift_id'])
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'cash_shift_id' => 'Выбранная кассовая смена уже закрыта. Обновите страницу и выберите открытую смену.',
                ]);
            }

            $customer = $this->resolveCustomer($data, $source);
            $product = Product::query()->with('membershipPlan')->where('is_active', true)->find($data['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(['product_id' => 'Товар или тариф недоступен для продажи.']);
            }

            if ($product->membershipPlan && ! $product->membershipPlan->is_active) {
                throw ValidationException::withMessages(['product_id' => 'Связанный тариф членства отключён. Включите тариф перед продажей.']);
            }

            $quantity = (int) $data['quantity'];
            $quote = $this->pricing->forProduct($product, $customer);
            $unitPrice = (float) $quote['price'];
            $total = round($unitPrice * $quantity, 2);

            $order = Order::query()->create([
                'number' => $this->orderNumber(),
                'customer_id' => $customer->id,
                'status' => 'completed',
                'payment_status' => 'paid',
                'subtotal' => $total,
                'total' => $total,
                'source' => $source,
                'paid_at' => now(),
            ]);

            $visitsLeft = $product->visits_count
                ? (int) $product->visits_count * $quantity
                : ($product->type === 'ticket' ? $quantity : null);

            $item = $order->items()->create([
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'base_price' => (float) $quote['base'],
                'price' => $unitPrice,
                'pricing_meta' => $quote,
                'total' => $total,
                'ticket_code' => $this->ticketCode(),
                'valid_until' => now()->addDays(max(1, (int) $product->validity_days))->toDateString(),
                'visits_left' => $visitsLeft,
            ]);

            $order->payments()->create([
                'provider' => 'pos_'.$data['payment_method'],
                'status' => 'paid',
                'amount' => $total,
                'paid_at' => now(),
                'payload' => [
                    'method' => $data['payment_method'],
                    'cash_shift_id' => $shift->id,
                    'cash_register_id' => $shift->cash_register_id,
                    'sold_by' => $seller->id,
                    'source' => $source,
                ],
            ]);

            CashTransaction::query()->create([
                'cash_shift_id' => $shift->id,
                'user_id' => $seller->id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'type' => 'sale',
                'method' => $data['payment_method'],
                'amount' => $total,
                'description' => 'POS '.$order->number.' · '.$product->name.' × '.$quantity,
                'occurred_at' => now(),
            ]);

            if ($product->type === 'gift') {
                for ($i = 0; $i < $quantity; $i++) {
                    Certificate::query()->create([
                        'serial' => $this->certificateSerial(),
                        'token' => Str::random(48),
                        'order_item_id' => $item->id,
                        'customer_id' => $customer->id,
                        'product_id' => $product->id,
                        'recipient_name' => $customer->name,
                        'amount' => $unitPrice,
                        'status' => 'active',
                        'valid_from' => today(),
                        'valid_until' => now()->addDays(max(1, (int) $product->validity_days))->toDateString(),
                    ]);
                }
            }

            if ($product->membershipPlan) {
                $plan = $product->membershipPlan;
                for ($i = 0; $i < $quantity; $i++) {
                    Membership::query()->create([
                        'number' => $this->membershipNumber(),
                        'customer_id' => $customer->id,
                        'primary_holder_id' => $customer->id,
                        'membership_plan_id' => $plan->id,
                        'order_item_id' => $item->id,
                        'status' => 'active',
                        'starts_on' => today(),
                        'ends_on' => today()->copy()->addDays(max(1, (int) $plan->duration_days) - 1),
                        'visits_total' => $plan->visits_included,
                        'visits_used' => 0,
                        'freeze_days_total' => $plan->freeze_days,
                        'freeze_days_used' => 0,
                        'guest_visits_left' => $plan->guest_visits,
                        'auto_renew' => false,
                        'price_paid' => $unitPrice,
                        'notes' => 'Автоматически создано первичной продажей '.$order->number.'.',
                    ]);
                }
            }

            return $order->load(['customer', 'items.product.membershipPlan', 'payments']);
        });
    }

    private function resolveCustomer(array $data, string $source): Customer
    {
        if (! empty($data['customer_id'])) {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $updates = [];
            foreach (['name', 'email', 'birth_date'] as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                    $updates[$field] = $data[$field];
                }
            }
            if ($updates) {
                $customer->update($updates);
            }
            return $customer;
        }

        $phone = $this->normalizePhone((string) $data['phone']);
        if ($phone === '') {
            throw ValidationException::withMessages(['phone' => 'Укажите телефон клиента.']);
        }

        $customer = $this->findByNormalizedPhone($phone);
        $payload = [
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'source' => $source,
        ];

        if ($customer) {
            $customer->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            return $customer;
        }

        return Customer::query()->create($payload);
    }

    private function findByNormalizedPhone(string $phone): ?Customer
    {
        return Customer::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '(', ''), ')', ''), '-', ''), '.', '') = ?", [$phone])
            ->first();
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    private function orderNumber(): string
    {
        do {
            $number = 'POS-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    private function ticketCode(): string
    {
        do {
            $code = 'T-'.Str::upper(Str::random(10));
        } while (DB::table('order_items')->where('ticket_code', $code)->exists());

        return $code;
    }

    private function membershipNumber(): string
    {
        do {
            $number = 'MEM-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Membership::query()->where('number', $number)->exists());

        return $number;
    }

    private function certificateSerial(): string
    {
        do {
            $serial = 'GC-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Certificate::query()->where('serial', $serial)->exists());

        return $serial;
    }
}
