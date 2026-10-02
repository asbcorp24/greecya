<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\TrainerPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrainerController extends Controller
{
    public function index()
    {
        return view('admin.trainers.index', [
            'trainers' => Trainer::query()
                ->with(['photos'])
                ->withCount('slots')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['photo_path'] = $request->file('photo')?->store('trainers/main', 'public');
        $data['is_active'] = $request->boolean('is_active');

        $trainer = Trainer::query()->create($data);

        $this->savePhotos($request, $trainer);

        return back()->with('success', 'Тренер добавлен.');
    }

    public function update(Request $request, Trainer $trainer)
    {
        $data = $this->validated($request, true, $trainer);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $trainer->slug ?? $data['name'], $trainer);

        if ($request->hasFile('photo')) {
            if ($trainer->photo_path) {
                Storage::disk('public')->delete($trainer->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('trainers/main', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');
        $trainer->update($data);

        $this->savePhotos($request, $trainer);

        return back()->with('success', 'Данные тренера обновлены.');
    }

    public function uploadPhotos(Request $request, Trainer $trainer)
    {
        $request->validate([
            'gallery_images' => ['required', 'array', 'min:1', 'max:20'],
            'gallery_images.*' => ['image', 'max:8192'],
        ]);

        $this->savePhotos($request, $trainer);

        return back()->with('success', 'Фотографии тренера добавлены.');
    }

    public function updatePhoto(Request $request, Trainer $trainer, TrainerPhoto $photo)
    {
        abort_unless($photo->trainer_id === $trainer->id, 404);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $photo->update($data);

        return back()->with('success', 'Фотография обновлена.');
    }

    public function destroyPhoto(Trainer $trainer, TrainerPhoto $photo)
    {
        abort_unless($photo->trainer_id === $trainer->id, 404);

        $path = $photo->image_path;
        $photo->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Фотография удалена.');
    }

    public function destroy(Trainer $trainer)
    {
        abort_if(
            $trainer->slots()->where('starts_at', '>=', now())->exists(),
            422,
            'Нельзя удалить тренера с будущими занятиями. Сначала отключите его.'
        );

        foreach ($trainer->photos as $photo) {
            Storage::disk('public')->delete($photo->image_path);
        }

        if ($trainer->photo_path) {
            Storage::disk('public')->delete($trainer->photo_path);
        }

        $trainer->delete();

        return back()->with('success', 'Тренер удалён.');
    }

    private function validated(Request $request, bool $updating = false, ?Trainer $trainer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('trainers', 'slug')->ignore($trainer?->id),
            ],
            'specialization' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:10000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'gallery_images' => ['nullable', 'array', 'max:20'],
            'gallery_images.*' => ['image', 'max:8192'],
        ]);
    }

    private function savePhotos(Request $request, Trainer $trainer): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        $startOrder = max(100, ((int) $trainer->photos()->max('sort_order')) + 10);

        foreach ($request->file('gallery_images', []) as $index => $image) {
            $trainer->photos()->create([
                'image_path' => $image->store('trainers/'.$trainer->id.'/gallery', 'public'),
                'caption' => null,
                'sort_order' => $startOrder + ($index * 10),
                'is_active' => true,
            ]);
        }
    }

    private function uniqueSlug(string $value, ?Trainer $ignore = null): string
    {
        $base = Str::slug($value) ?: 'trainer';
        $slug = $base;
        $suffix = 2;

        while (Trainer::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
