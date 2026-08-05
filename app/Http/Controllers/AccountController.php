<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgressEntry;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer = $request->user()->customer;
        $customer->load([
            'bookings' => fn ($q) => $q->with(['service', 'slot', 'trainer'])->latest()->limit(20),
            'orders' => fn ($q) => $q->with(['items.product'])->where('payment_status', 'paid')->latest()->limit(20),
            'certificates' => fn ($q) => $q->with('product')->latest(),
            'visits' => fn ($q) => $q->latest('visited_at')->limit(20),
            'trainingPlans' => fn ($q) => $q->with(['trainer', 'items', 'progressEntries'])->latest(),
            'progressEntries' => fn ($q) => $q->latest('recorded_on')->limit(20),
        ]);
        return view('account.dashboard', compact('customer'));
    }
    public function updateProfile(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:40'], 'birth_date' => ['nullable', 'date', 'before:today']]);
        $request->user()->update(['name' => $data['name'], 'phone' => $data['phone']]);
        $request->user()->customer->update($data);
        return back()->with('success', 'Профиль обновлён.');
    }
    public function storeProgress(Request $request)
    {
        $customer = $request->user()->customer;
        $data = $request->validate([
            'training_plan_id' => ['nullable', 'integer'], 'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'weight' => ['nullable', 'numeric', 'min:20', 'max:300'], 'distance_meters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'], 'note' => ['nullable', 'string', 'max:2000'],
        ]);
        if (! empty($data['training_plan_id'])) abort_unless($customer->trainingPlans()->whereKey($data['training_plan_id'])->exists(), 403);
        TrainingProgressEntry::create($data + ['customer_id' => $customer->id]);
        return back()->with('success', 'Результат тренировки сохранён.');
    }
}
