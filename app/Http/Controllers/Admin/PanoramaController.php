<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Panorama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PanoramaController extends Controller
{
    public function index()
    {
        return view('admin.panoramas.index', [
            'panoramas' => Panorama::query()
                ->orderByDesc('is_homepage')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $makeHomepage = $request->boolean('is_homepage');

        DB::transaction(function () use ($request, $data, $makeHomepage) {
            if ($makeHomepage) {
                Panorama::query()->update(['is_homepage' => false]);
            }

            Panorama::query()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'image_path' => $request->file('image')->store('panoramas', 'public'),
                'sort_order' => $data['sort_order'] ?? 100,
                'is_active' => $makeHomepage ? true : $request->boolean('is_active'),
                'is_homepage' => $makeHomepage,
            ]);
        });

        return back()->with('success', '360° панорама добавлена.');
    }

    public function update(Request $request, Panorama $panorama)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $makeHomepage = $request->boolean('is_homepage');
        $newImagePath = null;

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('panoramas', 'public');
        }

        DB::transaction(function () use ($request, $data, $panorama, $makeHomepage, $newImagePath) {
            if ($makeHomepage) {
                Panorama::query()
                    ->where('id', '!=', $panorama->id)
                    ->update(['is_homepage' => false]);
            }

            $oldImagePath = $panorama->image_path;

            $panorama->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'image_path' => $newImagePath ?: $oldImagePath,
                'sort_order' => $data['sort_order'] ?? 100,
                'is_active' => $makeHomepage ? true : $request->boolean('is_active'),
                'is_homepage' => $makeHomepage,
            ]);

            if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }
        });

        return back()->with('success', '360° панорама обновлена.');
    }

    public function destroy(Panorama $panorama)
    {
        $path = $panorama->image_path;
        $panorama->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', '360° панорама удалена.');
    }
}
