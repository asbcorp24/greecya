<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\DynamicPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request, DynamicPricingService $pricing)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:190'],
            'privacy' => ['accepted'],
        ]);

        $order = DB::transaction(function () use ($data, $pricing) {
            $product = Product::query()->where('is_active', true)->findOrFail($data['product_id']);
            $customer = Customer::query()->updateOrCreate(
                ['phone' => preg_replace('/\D+/', '', $data['phone'])],
                ['name' => $data['name'], 'email' => $data['email'], 'source' => 'shop']
            );
            $quote = $pricing->forProduct($product,$customer);
            $quantity = (int)$data['quantity'];
            $total = $quote['price'] * $quantity;

            $order = Order::query()->create([
                'number' => 'GRE-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'customer_id' => $customer->id,
                'status' => 'new',
                'payment_status' => 'pending',
                'subtotal' => $total,
                'total' => $total,
                'source' => 'site',
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'base_price' => $quote['base'],
                'price' => $quote['price'],
                'pricing_meta' => $quote,
                'total' => $total,
                'visits_left' => $product->visits_count ? $product->visits_count * $quantity : null,
            ]);

            Payment::query()->create([
                'order_id' => $order->id,
                'provider' => config('payment.provider'),
                'status' => 'pending',
                'amount' => $total,
            ]);

            return $order;
        });

        return redirect()->route('order.success', $order);
    }

    public function success(Order $order)
    {
        return view('catalog.success', ['order' => $order->load(['customer', 'items'])]);
    }
}
