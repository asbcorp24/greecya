<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index', [
            'products' => Product::query()->with('membershipPlan')->orderBy('sort_order')->get(),
            'membershipPlans' => MembershipPlan::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['ticket', 'subscription', 'gift'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'visits_count' => ['nullable', 'integer', 'min:1'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:1095'],
            'membership_plan_id' => ['nullable', 'integer', 'exists:membership_plans,id'],
        ]);

        DB::transaction(function () use ($data) {
            $planId = $data['membership_plan_id'] ?? null;
            unset($data['membership_plan_id']);

            $product = Product::query()->create($data + [
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
                'is_active' => true,
            ]);

            if ($planId) {
                MembershipPlan::query()->whereKey($planId)->update(['product_id' => $product->id]);
            }
        });

        return back()->with('success', 'Товар добавлен.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'membership_plan_id' => ['nullable', 'integer', 'exists:membership_plans,id'],
        ]);

        DB::transaction(function () use ($request, $product, $data) {
            $product->update([
                'price' => $data['price'],
                'is_active' => $request->boolean('is_active'),
            ]);

            MembershipPlan::query()->where('product_id', $product->id)->update(['product_id' => null]);
            if (! empty($data['membership_plan_id'])) {
                MembershipPlan::query()->whereKey($data['membership_plan_id'])->update(['product_id' => $product->id]);
            }
        });

        return back()->with('success', 'Товар обновлён.');
    }
}
