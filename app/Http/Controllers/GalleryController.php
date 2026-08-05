<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;

class GalleryController extends Controller
{
    public function index()
    {
        $albums = GalleryAlbum::query()->where('is_published', true)->with(['photos' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')])->orderBy('sort_order')->latest('id')->get();
        return view('gallery.index', compact('albums'));
    }
    public function show(GalleryAlbum $album)
    {
        abort_unless($album->is_published, 404);
        $album->load(['photos' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')->orderBy('id')]);
        return view('gallery.show', compact('album'));
    }
}
