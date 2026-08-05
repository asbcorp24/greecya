<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index', ['products' => Product::query()->orderBy('sort_order')->get()]);
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
        ]);

        Product::query()->create($data + ['slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)), 'is_active' => true]);

        return back()->with('success', 'Товар добавлен.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update(['price' => $data['price'], 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Товар обновлён.');
    }
}
