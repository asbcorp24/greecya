<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Trainer;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanItem;
use App\Models\TrainingProgressEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingPlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = TrainingPlan::query()->with(['customer', 'trainer', 'items', 'progressEntries'])->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))->latest()->paginate(15)->withQueryString();
        return view('admin.training-plans.index', ['plans' => $plans, 'customers' => Customer::query()->orderBy('name')->get(), 'trainers' => Trainer::query()->where('is_active', true)->orderBy('sort_order')->get()]);
    }
    public function store(Request $request) { TrainingPlan::create($this->validated($request)); return back()->with('success', 'План тренировок создан.'); }
    public function update(Request $request, TrainingPlan $plan) { $plan->update($this->validated($request)); return back()->with('success', 'План обновлён.'); }
    public function destroy(TrainingPlan $plan) { $plan->delete(); return back()->with('success', 'План удалён.'); }
    public function storeItem(Request $request, TrainingPlan $plan)
    {
        $data = $request->validate(['day_label' => ['nullable', 'string', 'max:100'], 'exercise' => ['required', 'string', 'max:255'], 'sets' => ['nullable', 'integer', 'min:1', 'max:100'], 'reps' => ['nullable', 'string', 'max:100'], 'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'], 'distance_meters' => ['nullable', 'integer', 'min:1', 'max:100000'], 'notes' => ['nullable', 'string', 'max:2000'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999']]);
        $plan->items()->create($data);
        return back()->with('success', 'Упражнение добавлено.');
    }
    public function destroyItem(TrainingPlanItem $item) { $item->delete(); return back()->with('success', 'Упражнение удалено.'); }
    public function storeProgress(Request $request, TrainingPlan $plan)
    {
        $data = $request->validate(['recorded_on' => ['required', 'date'], 'weight' => ['nullable', 'numeric', 'min:20', 'max:300'], 'distance_meters' => ['nullable', 'integer', 'min:0', 'max:100000'], 'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'], 'note' => ['nullable', 'string', 'max:2000'], 'coach_comment' => ['nullable', 'string', 'max:3000']]);
        TrainingProgressEntry::create($data + ['customer_id' => $plan->customer_id, 'training_plan_id' => $plan->id]);
        return back()->with('success', 'Результат и комментарий сохранены.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['customer_id' => ['required', 'exists:customers,id'], 'trainer_id' => ['nullable', 'exists:trainers,id'], 'title' => ['required', 'string', 'max:190'], 'goal' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'schedule_text' => ['nullable', 'string', 'max:3000'], 'recommendations' => ['nullable', 'string', 'max:5000'], 'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'], 'status' => ['required', Rule::in(['draft', 'active', 'completed', 'paused'])]]);
    }
}
