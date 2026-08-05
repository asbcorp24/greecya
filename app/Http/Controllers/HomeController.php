<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use App\Models\HeroSlide;
use App\Models\NewsPost;
use App\Models\Product;
use App\Models\Service;
use App\Models\Trainer;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'slides' => HeroSlide::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'services' => Service::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('sort_order')->take(3)->get(),
            'trainers' => Trainer::query()->where('is_active', true)->orderBy('sort_order')->take(6)->get(),
            'latestNews' => NewsPost::query()->where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->latest('published_at')->take(3)->get(),
            'galleryAlbums' => GalleryAlbum::query()->where('is_published', true)->with(['photos' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')->limit(4)])->orderBy('sort_order')->take(3)->get(),
        ]);
    }
}
