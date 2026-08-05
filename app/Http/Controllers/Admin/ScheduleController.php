<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\Trainer;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.schedule.index', [
            'slots' => ScheduleSlot::query()->with(['service', 'trainer'])->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->paginate(30),
            'services' => Service::query()->where('is_active', true)->orderBy('name')->get(),
            'trainers' => Trainer::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'trainer_id' => ['nullable', 'exists:trainers,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        ScheduleSlot::query()->create($data + ['booked_count' => 0, 'status' => 'open']);

        return back()->with('success', 'Время добавлено в расписание.');
    }

    public function destroy(ScheduleSlot $slot)
    {
        abort_if($slot->booked_count > 0, 422, 'Нельзя удалить слот с записями. Закройте его или перенесите клиентов.');
        $slot->delete();

        return back()->with('success', 'Время удалено.');
    }
}
