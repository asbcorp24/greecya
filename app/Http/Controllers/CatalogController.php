<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\DynamicPricingService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __invoke(Request $request, DynamicPricingService $pricing)
    {
        $customer = $request->user()?->role === 'customer' ? $request->user()->customer : null;
        $products = Product::query()->where('is_active', true)->orderBy('sort_order')->get()->map(function(Product $product) use ($pricing,$customer){
            $quote = $pricing->forProduct($product,$customer);
            $product->setAttribute('display_price',$quote['price']);
            $product->setAttribute('pricing_quote',$quote);
            return $product;
        });
        return view('catalog.index', compact('products'));
    }
}
