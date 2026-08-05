<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()->with(['customer', 'items.certificates'])->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))->latest()->paginate(25)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }
    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'processing', 'completed', 'cancelled'])], 'payment_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])]]);
        DB::transaction(function () use ($order, $data) {
            $order->load('items.product');
            $becamePaid = $order->payment_status !== 'paid' && $data['payment_status'] === 'paid';
            $order->update($data + ['paid_at' => $data['payment_status'] === 'paid' ? ($order->paid_at ?: now()) : $order->paid_at]);
            if ($becamePaid) {
                foreach ($order->items as $item) {
                    $item->update(['ticket_code' => $item->ticket_code ?: 'T-'.Str::upper(Str::random(10)), 'valid_until' => $item->valid_until ?: now()->addDays($item->product?->validity_days ?? 30)->toDateString()]);
                    if ($item->product?->type === 'gift') {
                        for ($i = 0; $i < $item->quantity; $i++) Certificate::create([
                            'serial' => $this->serial(), 'token' => Str::random(48), 'order_item_id' => $item->id, 'customer_id' => $order->customer_id,
                            'product_id' => $item->product_id, 'recipient_name' => $order->customer->name, 'amount' => $item->price, 'status' => 'active',
                            'valid_from' => today(), 'valid_until' => now()->addDays($item->product->validity_days ?? 180)->toDateString(),
                        ]);
                    }
                }
                $order->payments()->latest()->first()?->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });
        return back()->with('success', 'Заказ обновлён.');
    }
    private function serial(): string
    {
        do $serial = 'GC-'.now()->format('ymd').'-'.Str::upper(Str::random(6)); while (Certificate::where('serial', $serial)->exists());
        return $serial;
    }
}
