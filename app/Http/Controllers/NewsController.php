<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;

class NewsController extends Controller
{
    public function index()
    {
        $posts = NewsPost::query()->where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->latest('published_at')->paginate(9);
        return view('news.index', compact('posts'));
    }
    public function show(NewsPost $post)
    {
        abort_unless($post->is_published && (! $post->published_at || $post->published_at->lte(now())), 404);
        return view('news.show', compact('post'));
    }
}
