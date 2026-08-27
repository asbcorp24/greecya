<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::query()
            ->withCount(['slots', 'bookings'])
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
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name']);
        $data['requires_trainer'] = $request->boolean('requires_trainer');
        $data['online_booking'] = $request->boolean('online_booking');
        $data['is_active'] = $request->boolean('is_active');

        $service = Service::query()->create($data);

        return redirect()->route('admin.services.edit', $service)
            ->with('success', 'Услуга «'.$service->name.'» создана. Теперь можно добавить её в расписание.');
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $data = $this->validated($request, $service);

        if (! empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        } else {
            unset($data['slug']);
        }

        $data['requires_trainer'] = $request->boolean('requires_trainer');
        $data['online_booking'] = $request->boolean('online_booking');
        $data['is_active'] = $request->boolean('is_active');

        $service->update($data);

        return back()->with('success', 'Услуга обновлена.');
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
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'requires_trainer' => ['nullable', 'boolean'],
            'online_booking' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
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
