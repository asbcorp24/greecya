<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::query()
            ->where('is_active', true)
            ->with(['photos' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->limit(1)])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $categories = Service::query()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('services.index', compact('services', 'categories'));
    }

    public function show(Service $service)
    {
        abort_unless($service->is_active, 404);

        $service->load([
            'photos' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
        ]);

        $upcomingSlots = $service->slots()
            ->with('trainer:id,name,specialization')
            ->where('status', 'open')
            ->where('starts_at', '>', now())
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $related = Service::query()
            ->where('is_active', true)
            ->where('id', '!=', $service->id)
            ->where('category', $service->category)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'upcomingSlots', 'related'));
    }
}
