<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use App\Models\NewsPost;
use App\Support\SiteSettings;

class SeoController extends Controller
{
    public function sitemap()
    {
        $staticUrls = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('booking.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('catalog.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('news.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('gallery.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('offer'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ]);

        $news = NewsPost::query()->where('is_published', true)->get()->map(fn ($post) => [
            'loc' => route('news.show', $post),
            'lastmod' => $post->updated_at?->toAtomString(),
            'priority' => '0.7',
            'changefreq' => 'monthly',
        ]);

        $albums = GalleryAlbum::query()->where('is_published', true)->get()->map(fn ($album) => [
            'loc' => route('gallery.show', $album),
            'lastmod' => $album->updated_at?->toAtomString(),
            'priority' => '0.6',
            'changefreq' => 'monthly',
        ]);

        return response()->view('seo.sitemap', ['urls' => $staticUrls->concat($news)->concat($albums)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $allow = SiteSettings::get('seo_allow_indexing', true);
        $content = $allow
            ? "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /account\nSitemap: ".route('seo.sitemap')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
