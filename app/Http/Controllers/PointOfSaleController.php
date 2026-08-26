<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\DynamicPricingService;
use App\Services\PointOfSaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PointOfSaleController extends Controller
{
    public function reception(Request $request, DynamicPricingService $pricing)
    {
        $this->authorizePos($request, false);
        return $this->screen($request, $pricing, 'reception');
    }

    public function admin(Request $request, DynamicPricingService $pricing)
    {
        $this->authorizePos($request, true);
        return $this->screen($request, $pricing, 'admin');
    }

    public function storeReception(Request $request, PointOfSaleService $sales)
    {
        $this->authorizePos($request, false);
        $order = $sales->sell($this->validatedSale($request), $request->user(), 'reception_pos');

        return redirect()
            ->route('reception.index', ['customer' => $order->customer_id])
            ->with('success', 'Продажа '.$order->number.' оплачена. Билет/абонемент активирован — выберите зону и выполните проход.');
    }

    public function storeAdmin(Request $request, PointOfSaleService $sales)
    {
        $this->authorizePos($request, true);
        $order = $sales->sell($this->validatedSale($request), $request->user(), 'manager_pos');

        return redirect()
            ->route('admin.sales.index', ['customer' => $order->customer_id, 'order' => $order->number])
            ->with('success', 'Продажа '.$order->number.' оплачена и зарегистрирована в кассе.');
    }

    private function screen(Request $request, DynamicPricingService $pricing, string $mode)
    {
        $query = trim((string) $request->input('q', ''));
        $results = collect();
        if ($query !== '') {
            $like = '%'.$query.'%';
            $results = Customer::query()
                ->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        $customer = $request->integer('customer')
            ? Customer::query()->find($request->integer('customer'))
            : ($results->count() === 1 ? $results->first() : null);

        $products = Product::query()
            ->with('membershipPlan')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $quotes = [];
        foreach ($products as $product) {
            $quotes[$product->id] = $pricing->forProduct($product, $customer);
        }

        $openShifts = CashShift::query()
            ->with('register')
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        $recentSales = Order::query()
            ->with(['customer', 'items'])
            ->whereIn('source', ['reception_pos', 'manager_pos'])
            ->latest('paid_at')
            ->limit(15)
            ->get();

        $lastOrder = null;
        if ($request->filled('order')) {
            $lastOrder = Order::query()
                ->with(['customer', 'items.product.membershipPlan', 'payments'])
                ->where('number', $request->string('order'))
                ->first();
        }

        return view('pos.index', [
            'mode' => $mode,
            'layout' => $mode === 'admin' ? 'admin.layout' : 'workspace',
            'query' => $query,
            'results' => $results,
            'customer' => $customer,
            'products' => $products,
            'quotes' => $quotes,
            'openShifts' => $openShifts,
            'recentSales' => $recentSales,
            'lastOrder' => $lastOrder,
        ]);
    }

    private function validatedSale(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'name' => ['required_without:customer_id', 'nullable', 'string', 'max:120'],
            'phone' => ['required_without:customer_id', 'nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'sbp', 'bank'])],
            'cash_shift_id' => ['required', 'integer', 'exists:cash_shifts,id'],
        ]);
    }

    private function authorizePos(Request $request, bool $adminScreen): void
    {
        $user = $request->user();
        abort_unless($user && $user->hasPermission('sales.pos'), 403);

        if ($adminScreen) {
            abort_unless(in_array($user->role, ['admin', 'director', 'manager', 'cashier'], true), 403);
        }
    }
}
