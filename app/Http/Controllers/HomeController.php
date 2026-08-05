<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'services' => Service::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('sort_order')->take(3)->get(),
        ]);
    }
}
