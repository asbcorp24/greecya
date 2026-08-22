<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGoal;
use App\Models\CustomerNote;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->withCount(['bookings', 'orders','visits'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(fn ($sub) => $sub->where('name', 'like', $q)->orWhere('phone', 'like', $q)->orWhere('email', 'like', $q));
            })
            ->latest()->paginate(30)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Request $request, Customer $customer)
    {
        $customer->load([
            'wallet','accessCards','medicalClearances','certificates',
            'memberships.plan','memberships.freezes','visits.membership',
            'bookings.service','bookings.slot','bookings.trainer',
            'orders.items','orders.payments','trainingPlans.trainer','trainingPlans.progressEntries',
            'interactions','documents','staffNotes.user','goals.trainer',
            'families.wallet','families.members.customer','swimGroupMemberships.group.trainer','swimGroupMemberships.progress',
        ]);

        $debt = $customer->orders->whereNotIn('payment_status',['paid','refunded'])->sum(fn($order)=>(float)$order->total);
        $preferredTrainer = $customer->trainingPlans->where('status','active')->first()?->trainer;

        return view('admin.customers.show', [
            'customer'=>$customer,
            'debt'=>$debt,
            'preferredTrainer'=>$preferredTrainer,
            'trainers'=>Trainer::where('is_active',true)->orderBy('name')->get(),
            'canPersonal'=>$request->user()->canSeePersonalData(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'phone'=>'required|string|max:30',
            'email'=>'nullable|email|max:190',
            'birth_date'=>'nullable|date',
            'gender'=>['nullable',Rule::in(['male','female'])],
            'emergency_contact'=>'nullable|string|max:120',
            'notes'=>'nullable|string|max:5000',
            'source'=>'nullable|string|max:80',
        ]);
        $data['marketing_consent'] = $request->boolean('marketing_consent');
        $customer->update($data);
        return back()->with('success','Карточка клиента обновлена.');
    }

    public function storeNote(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'type'=>['required',Rule::in(['note','service','sales','trainer','medical','complaint'])],
            'body'=>'required|string|max:5000',
        ]);
        CustomerNote::create($data + ['customer_id'=>$customer->id,'user_id'=>$request->user()->id,'is_private'=>true]);
        return back()->with('success','Комментарий добавлен в историю клиента.');
    }

    public function storeGoal(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'trainer_id'=>'nullable|exists:trainers,id',
            'title'=>'required|string|max:190',
            'description'=>'nullable|string|max:3000',
            'target_date'=>'nullable|date',
            'progress_percent'=>'required|integer|min:0|max:100',
        ]);
        CustomerGoal::create($data + ['customer_id'=>$customer->id,'status'=>'active']);
        return back()->with('success','Цель клиента добавлена.');
    }

    public function updateGoal(Request $request, Customer $customer, CustomerGoal $goal)
    {
        abort_unless((int)$goal->customer_id === (int)$customer->id, 404);
        $data = $request->validate([
            'status'=>['required',Rule::in(['active','completed','cancelled'])],
            'progress_percent'=>'required|integer|min:0|max:100',
            'description'=>'nullable|string|max:3000',
        ]);
        $goal->update($data);
        return back()->with('success','Прогресс по цели обновлён.');
    }
}
