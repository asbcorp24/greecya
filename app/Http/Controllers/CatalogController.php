<?php

namespace App\Http\Controllers;

use App\Models\Product;

class CatalogController extends Controller
{
    public function __invoke()
    {
        return view('catalog.index', [
            'products' => Product::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
