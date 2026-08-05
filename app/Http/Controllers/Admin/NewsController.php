<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    public function index() { return view('admin.news.index', ['posts' => NewsPost::query()->latest()->paginate(20)]); }
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['image_path'] = $request->file('image')?->store('news', 'public');
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['published_at'] ?: ($data['is_published'] ? now() : null);
        NewsPost::create($data);
        return back()->with('success', 'Новость добавлена.');
    }
    public function update(Request $request, NewsPost $post)
    {
        $data = $this->validated($request, $post);
        if ($request->hasFile('image')) {
            if ($post->image_path) Storage::disk('public')->delete($post->image_path);
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['published_at'] ?: ($data['is_published'] ? ($post->published_at ?: now()) : null);
        $post->update($data);
        return back()->with('success', 'Новость обновлена.');
    }
    public function destroy(NewsPost $post)
    {
        if ($post->image_path) Storage::disk('public')->delete($post->image_path);
        $post->delete();
        return back()->with('success', 'Новость удалена.');
    }
    private function validated(Request $request, ?NewsPost $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('news_posts', 'slug')->ignore($post)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'image' => [$post ? 'nullable' : 'required', 'image', 'max:5120'],
        ]);
    }
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'news'; $slug = $base; $i = 2;
        while (NewsPost::where('slug', $slug)->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }
}
