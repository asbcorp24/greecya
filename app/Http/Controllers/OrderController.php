<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:190'],
            'privacy' => ['accepted'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $product = Product::query()->where('is_active', true)->findOrFail($data['product_id']);
            $customer = Customer::query()->updateOrCreate(
                ['phone' => preg_replace('/\D+/', '', $data['phone'])],
                ['name' => $data['name'], 'email' => $data['email'], 'source' => 'shop']
            );
            $total = $product->price * $data['quantity'];

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
                'quantity' => $data['quantity'],
                'price' => $product->price,
                'total' => $total,
                'visits_left' => $product->visits_count ? $product->visits_count * $data['quantity'] : null,
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
