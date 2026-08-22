<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PayrollAccrual;
use App\Models\PayrollRule;
use App\Models\Service;
use App\Models\StaffShift;
use App\Models\Trainer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $period = Carbon::parse($request->input('period', now()->format('Y-m').'-01'))->startOfMonth();

        return view('admin.staff.index', [
            'trainers' => Trainer::orderBy('name')->get(),
            'users' => User::whereIn('role', ['admin', 'manager'])->orderBy('name')->get(),
            'services' => Service::orderBy('name')->get(),
            'shifts' => StaffShift::with(['trainer', 'user'])->whereBetween('starts_at', [$period, $period->copy()->endOfMonth()])->orderBy('starts_at')->get(),
            'rules' => PayrollRule::with(['trainer', 'service'])->where('is_active', true)->get(),
            'accruals' => PayrollAccrual::with(['trainer', 'rule'])->whereDate('period_month', $period->toDateString())->get(),
            'period' => $period,
        ]);
    }

    public function storeShift(Request $request)
    {
        $data = $request->validate([
            'trainer_id' => 'nullable|exists:trainers,id',
            'user_id' => 'nullable|exists:users,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'type' => 'required|string|max:50',
        ]);
        abort_if(empty($data['trainer_id']) && empty($data['user_id']), 422, 'Выберите сотрудника или тренера.');
        StaffShift::create($data + ['status' => 'planned', 'worked_minutes' => 0]);
        return back()->with('success', 'Смена сотрудника добавлена.');
    }

    public function updateShift(Request $request, StaffShift $shift)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['planned', 'worked', 'absent', 'cancelled'])],
            'worked_minutes' => 'required|integer|min:0|max:1440',
        ]);
        $shift->update($data);
        return back()->with('success', 'Фактическое время сохранено.');
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'trainer_id' => 'nullable|exists:trainers,id',
            'user_id' => 'nullable|exists:users,id',
            'service_id' => 'nullable|exists:services,id',
            'name' => 'required|string|max:190',
            'calc_type' => ['required', Rule::in(['fixed', 'hourly', 'session', 'visitor', 'percent_service'])],
            'rate' => 'required|numeric|min:0|max:1000000',
        ]);
        abort_if(empty($data['trainer_id']) && empty($data['user_id']), 422, 'Выберите сотрудника или тренера.');
        PayrollRule::create($data + ['is_active' => true]);
        return back()->with('success', 'Правило начисления создано.');
    }

    public function calculate(Request $request)
    {
        $data = $request->validate(['period' => 'required|date_format:Y-m']);
        $start = Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth();
        $end = $start->copy()->endOfMonth();

        foreach (PayrollRule::where('is_active', true)->get() as $rule) {
            $quantity = 0;
            $amount = 0;

            if ($rule->calc_type === 'fixed') {
                $quantity = 1;
                $amount = (float) $rule->rate;
            } elseif ($rule->calc_type === 'hourly') {
                $minutes = StaffShift::query()
                    ->where(function ($query) use ($rule) {
                        if ($rule->trainer_id) {
                            $query->where('trainer_id', $rule->trainer_id);
                            if ($rule->user_id) {
                                $query->orWhere('user_id', $rule->user_id);
                            }
                        } elseif ($rule->user_id) {
                            $query->where('user_id', $rule->user_id);
                        }
                    })
                    ->where('status', 'worked')
                    ->whereBetween('starts_at', [$start, $end])
                    ->sum('worked_minutes');
                $quantity = $minutes / 60;
                $amount = $quantity * (float) $rule->rate;
            } else {
                $bookings = Booking::query()
                    ->when($rule->trainer_id, fn ($q) => $q->where('trainer_id', $rule->trainer_id))
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->whereHas('slot', fn ($q) => $q->whereBetween('starts_at', [$start, $end]))
                    ->when($rule->service_id, fn ($q) => $q->where('service_id', $rule->service_id));

                if ($rule->calc_type === 'session') {
                    $quantity = $bookings->count();
                    $amount = $quantity * (float) $rule->rate;
                } elseif ($rule->calc_type === 'visitor') {
                    $quantity = $bookings->sum('people');
                    $amount = $quantity * (float) $rule->rate;
                } elseif ($rule->calc_type === 'percent_service') {
                    $quantity = (float) $bookings->sum('total');
                    $amount = $quantity * (float) $rule->rate / 100;
                }
            }

            PayrollAccrual::updateOrCreate(
                [
                    'trainer_id' => $rule->trainer_id,
                    'user_id' => $rule->user_id,
                    'payroll_rule_id' => $rule->id,
                    'period_month' => $start->toDateString(),
                ],
                [
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'description' => $rule->name,
                    'status' => 'accrued',
                ]
            );
        }

        return back()->with('success', 'Начисления рассчитаны за '.$start->format('m.Y').'.');
    }

    public function pay(PayrollAccrual $accrual)
    {
        $accrual->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', 'Начисление отмечено выплаченным.');
    }
}
