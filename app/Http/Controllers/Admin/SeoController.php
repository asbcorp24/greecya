<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SeoController extends Controller
{
    public function index()
    {
        return view('admin.seo.index', [
            'pages' => SeoPage::query()->orderBy('page_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['og_image_path'] = $request->file('og_image')?->store('seo', 'public');
        SeoPage::create($data);

        return back()->with('success', 'SEO-страница добавлена.');
    }

    public function update(Request $request, SeoPage $seoPage)
    {
        $data = $this->validated($request, $seoPage);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('og_image')) {
            if ($seoPage->og_image_path) {
                Storage::disk('public')->delete($seoPage->og_image_path);
            }
            $data['og_image_path'] = $request->file('og_image')->store('seo', 'public');
        }

        $seoPage->update($data);

        return back()->with('success', 'SEO-настройки сохранены.');
    }

    public function destroy(SeoPage $seoPage)
    {
        if ($seoPage->og_image_path) {
            Storage::disk('public')->delete($seoPage->og_image_path);
        }
        $seoPage->delete();

        return back()->with('success', 'SEO-страница удалена.');
    }

    private function validated(Request $request, ?SeoPage $seoPage = null): array
    {
        return $request->validate([
            'route_name' => ['required', 'string', 'max:190', Rule::unique('seo_pages', 'route_name')->ignore($seoPage)],
            'page_name' => ['required', 'string', 'max:190'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots' => ['required', Rule::in(['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'])],
            'schema_json' => ['nullable', 'json'],
            'og_image' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
