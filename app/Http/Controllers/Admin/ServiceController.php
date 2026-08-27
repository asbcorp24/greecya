<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::query()
            ->withCount(['slots', 'bookings', 'photos'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', $q)
                    ->orWhere('slug', 'like', $q)
                    ->orWhere('description', 'like', $q));
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->string('status')->toString() === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->string('status')->toString() === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => Service::query()->select('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function create()
    {
        return view('admin.services.create', [
            'service' => new Service([
                'duration_minutes' => 60,
                'price' => 0,
                'capacity' => 1,
                'sort_order' => 100,
                'requires_trainer' => false,
                'online_booking' => true,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $mainImage = $validated['main_image'] ?? null;
        $additionalImages = $validated['additional_images'] ?? [];
        unset($validated['main_image'], $validated['additional_images'], $validated['remove_main_image']);

        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? null, $validated['name']);
        $validated['requires_trainer'] = $request->boolean('requires_trainer');
        $validated['online_booking'] = $request->boolean('online_booking');
        $validated['is_active'] = $request->boolean('is_active');

        $service = Service::query()->create($validated);

        if ($mainImage) {
            $service->update([
                'main_image_path' => $mainImage->store('services/'.$service->id.'/main', 'public'),
            ]);
        }

        $this->storeAdditionalPhotos($service, $additionalImages);

        return redirect()->route('admin.services.edit', $service)
            ->with('success', 'Услуга «'.$service->name.'» создана. Заполните фотографии и при необходимости добавьте её в расписание.');
    }

    public function edit(Service $service)
    {
        $service->load('photos');

        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $this->validated($request, $service);
        $mainImage = $validated['main_image'] ?? null;
        $additionalImages = $validated['additional_images'] ?? [];
        unset($validated['main_image'], $validated['additional_images'], $validated['remove_main_image']);

        if (! empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        } else {
            unset($validated['slug']);
        }

        $validated['requires_trainer'] = $request->boolean('requires_trainer');
        $validated['online_booking'] = $request->boolean('online_booking');
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_main_image') && $service->main_image_path) {
            Storage::disk('public')->delete($service->main_image_path);
            $validated['main_image_path'] = null;
        }

        if ($mainImage) {
            if ($service->main_image_path) {
                Storage::disk('public')->delete($service->main_image_path);
            }
            $validated['main_image_path'] = $mainImage->store('services/'.$service->id.'/main', 'public');
        }

        $service->update($validated);
        $this->storeAdditionalPhotos($service, $additionalImages);

        return back()->with('success', 'Услуга и её фотографии обновлены.');
    }

    public function storePhotos(Request $request, Service $service)
    {
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'image', 'max:8192'],
        ]);

        $this->storeAdditionalPhotos($service, $data['images']);

        return back()->with('success', 'Дополнительные фотографии добавлены.');
    }

    public function updatePhoto(Request $request, Service $service, ServicePhoto $photo)
    {
        abort_unless($photo->service_id === $service->id, 404);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $photo->update([
            'caption' => $data['caption'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Параметры фотографии сохранены.');
    }

    public function destroyPhoto(Service $service, ServicePhoto $photo)
    {
        abort_unless($photo->service_id === $service->id, 404);

        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        return back()->with('success', 'Фотография удалена.');
    }

    public function toggle(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        return back()->with('success', $service->is_active ? 'Услуга включена.' : 'Услуга отключена. История расписания и записей сохранена.');
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => [
                'nullable', 'string', 'max:190', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('services', 'slug')->ignore($service?->id),
            ],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:10000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'requires_trainer' => ['nullable', 'boolean'],
            'online_booking' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'main_image' => ['nullable', 'image', 'max:8192'],
            'remove_main_image' => ['nullable', 'boolean'],
            'additional_images' => ['nullable', 'array', 'max:20'],
            'additional_images.*' => ['required', 'image', 'max:8192'],
        ]);
    }

    private function storeAdditionalPhotos(Service $service, iterable $images): void
    {
        $nextSort = ((int) $service->photos()->max('sort_order')) + 10;

        foreach ($images as $image) {
            $service->photos()->create([
                'image_path' => $image->store('services/'.$service->id.'/gallery', 'public'),
                'sort_order' => $nextSort,
                'is_active' => true,
            ]);
            $nextSort += 10;
        }
    }

    private function uniqueSlug(?string $slug, string $name): string
    {
        $base = Str::slug($slug ?: $name) ?: 'service';
        $candidate = $base;
        $suffix = 2;

        while (Service::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
