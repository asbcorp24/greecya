<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGoal;
use App\Models\CustomerNote;
use App\Models\Trainer;
use App\Services\CustomerAccessCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('customers.edit'), 403);

        return view('admin.customers.create', [
            'sources' => [
                'manual' => 'Вручную в CRM',
                'reception' => 'Ресепшен',
                'phone' => 'Телефонный звонок',
                'referral' => 'Рекомендация',
                'corporate' => 'Корпоративный клиент',
            ],
        ]);
    }

    public function store(Request $request, CustomerAccessCardService $accessCards)
    {
        abort_unless($request->user()->hasPermission('customers.edit'), 403);

        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:80'],
            'patronymic' => ['nullable', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'source' => ['required', Rule::in(['manual', 'reception', 'phone', 'referral', 'corporate'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'privacy_consent' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'issue_qr' => ['nullable', 'boolean'],
        ]);

        $phone = $this->normalizePhone((string) $data['phone']);
        if (strlen($phone) < 10) {
            throw ValidationException::withMessages(['phone' => 'Укажите корректный номер телефона.']);
        }

        if ($existing = $this->findByNormalizedPhone($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Клиент с таким телефоном уже есть в базе: '.$existing->name.' (ID '.$existing->id.').',
            ]);
        }

        $fullName = trim(implode(' ', array_filter([
            trim((string) $data['last_name']),
            trim((string) $data['first_name']),
            trim((string) ($data['patronymic'] ?? '')),
        ])));

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('customers/photos', 'public');
        }

        try {
            $customer = DB::transaction(function () use ($data, $phone, $fullName, $photoPath, $request, $accessCards) {
                $customer = Customer::query()->create([
                    'name' => $fullName,
                    'phone' => $phone,
                    'email' => $data['email'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'emergency_contact' => $data['emergency_contact'] ?? null,
                    'source' => $data['source'],
                    'notes' => $data['notes'] ?? null,
                    'photo_path' => $photoPath,
                    'privacy_consent_at' => now(),
                    'marketing_consent' => $request->boolean('marketing_consent'),
                ]);

                if ($request->boolean('issue_qr')) {
                    $accessCards->ensure($customer);
                }

                return $customer;
            });
        } catch (\Throwable $e) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            throw $e;
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Клиент создан.'.($request->boolean('issue_qr') ? ' QR-карта выдана.' : ''));
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

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    private function findByNormalizedPhone(string $phone): ?Customer
    {
        return Customer::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '(', ''), ')', ''), '-', ''), '.', '') = ?", [$phone])
            ->first();
    }
}
