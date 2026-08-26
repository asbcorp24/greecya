<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function index()
    {
        return view('admin.gallery.index', ['albums' => GalleryAlbum::with('photos')->orderBy('sort_order')->latest('id')->get()]);
    }
    public function storeAlbum(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:190'], 'description' => ['nullable', 'string', 'max:3000'], 'cover' => ['nullable', 'image', 'max:5120'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999']]);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['cover_path'] = $request->file('cover')?->store('gallery/covers', 'public');
        $data['is_published'] = $request->boolean('is_published');
        GalleryAlbum::create($data);
        return back()->with('success', 'Альбом создан.');
    }
    public function updateAlbum(Request $request, GalleryAlbum $album)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:190'], 'description' => ['nullable', 'string', 'max:3000'], 'cover' => ['nullable', 'image', 'max:5120'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999']]);
        if ($request->hasFile('cover')) {
            if ($album->cover_path) Storage::disk('public')->delete($album->cover_path);
            $data['cover_path'] = $request->file('cover')->store('gallery/covers', 'public');
        }
        $data['is_published'] = $request->boolean('is_published');
        $album->update($data);
        return back()->with('success', 'Альбом обновлён.');
    }
    public function destroyAlbum(GalleryAlbum $album)
    {
        foreach ($album->photos as $photo) Storage::disk('public')->delete($photo->image_path);
        if ($album->cover_path) Storage::disk('public')->delete($album->cover_path);
        $album->delete();
        return back()->with('success', 'Альбом удалён.');
    }
    public function storePhoto(Request $request, GalleryAlbum $album)
    {
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:190'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $title = $data['title'] ?? null;
        $caption = $data['caption'] ?? null;

        foreach ($request->file('images') as $index => $image) {
            $album->photos()->create([
                'image_path' => $image->store('gallery/photos', 'public'),
                'title' => $title ?: null,
                'caption' => $caption ?: null,
                'sort_order' => 100 + $index,
                'is_published' => true,
            ]);
        }

        if (! $album->cover_path && $album->photos()->exists()) {
            $album->update(['cover_path' => $album->photos()->oldest()->value('image_path')]);
        }

        return back()->with('success', 'Фотографии добавлены.');
    }
    public function updatePhoto(Request $request, GalleryPhoto $photo)
    {
        $data = $request->validate(['title' => ['nullable', 'string', 'max:190'], 'caption' => ['nullable', 'string', 'max:1000'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999']]);
        $data['is_published'] = $request->boolean('is_published');
        $photo->update($data);
        return back()->with('success', 'Фотография обновлена.');
    }
    public function destroyPhoto(GalleryPhoto $photo)
    {
        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();
        return back()->with('success', 'Фотография удалена.');
    }
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'album'; $slug = $base; $i = 2;
        while (GalleryAlbum::where('slug', $slug)->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }
}
