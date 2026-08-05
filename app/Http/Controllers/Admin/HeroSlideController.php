<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    public function index() { return view('admin.slides.index', ['slides' => HeroSlide::query()->orderBy('sort_order')->get()]); }
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image_path'] = $request->file('image')->store('slides', 'public');
        $data['is_active'] = $request->boolean('is_active');
        HeroSlide::create($data);
        return back()->with('success', 'Слайд добавлен.');
    }
    public function update(Request $request, HeroSlide $slide)
    {
        $data = $this->validated($request, true);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($slide->image_path);
            $data['image_path'] = $request->file('image')->store('slides', 'public');
        }
        $data['is_active'] = $request->boolean('is_active');
        $slide->update($data);
        return back()->with('success', 'Слайд обновлён.');
    }
    public function destroy(HeroSlide $slide)
    {
        Storage::disk('public')->delete($slide->image_path);
        $slide->delete();
        return back()->with('success', 'Слайд удалён.');
    }
    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:190'], 'subtitle' => ['nullable', 'string', 'max:1000'], 'button_text' => ['nullable', 'string', 'max:80'], 'button_url' => ['nullable', 'string', 'max:500'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'], 'image' => [$updating ? 'nullable' : 'required', 'image', 'max:8192']]);
    }
}
