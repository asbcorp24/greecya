<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $certificates = Certificate::query()->with(['customer', 'product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner->where('serial', 'like', $search)->orWhere('recipient_name', 'like', $search));
            })->latest()->paginate(25)->withQueryString();
        return view('admin.certificates.index', ['certificates' => $certificates, 'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']), 'products' => Product::query()->where('type', 'gift')->orderBy('name')->get()]);
    }
    public function store(Request $request)
    {
        $data = $request->validate(['customer_id' => ['nullable', 'exists:customers,id'], 'product_id' => ['nullable', 'exists:products,id'], 'recipient_name' => ['required', 'string', 'max:190'], 'sender_name' => ['nullable', 'string', 'max:190'], 'message' => ['nullable', 'string', 'max:2000'], 'amount' => ['required', 'numeric', 'min:0', 'max:1000000'], 'valid_from' => ['nullable', 'date'], 'valid_until' => ['required', 'date', 'after_or_equal:valid_from'], 'notes' => ['nullable', 'string', 'max:3000']]);
        Certificate::create($data + ['serial' => $this->serial(), 'token' => Str::random(48), 'status' => 'active']);
        return back()->with('success', 'Сертификат создан.');
    }
    public function update(Request $request, Certificate $certificate)
    {
        $data = $request->validate(['recipient_name' => ['required', 'string', 'max:190'], 'sender_name' => ['nullable', 'string', 'max:190'], 'message' => ['nullable', 'string', 'max:2000'], 'amount' => ['required', 'numeric', 'min:0', 'max:1000000'], 'valid_from' => ['nullable', 'date'], 'valid_until' => ['required', 'date', 'after_or_equal:valid_from'], 'status' => ['required', Rule::in(['active', 'used', 'cancelled'])], 'notes' => ['nullable', 'string', 'max:3000']]);
        $certificate->update($data);
        return back()->with('success', 'Сертификат обновлён.');
    }
    public function scan(Request $request)
    {
        $certificate = null;
        if ($request->filled('code')) {
            $code = trim((string) $request->input('code'));
            if (str_contains($code, '/certificates/')) $code = basename(parse_url($code, PHP_URL_PATH));
            $certificate = Certificate::query()->with(['customer', 'product'])->where('token', $code)->orWhere('serial', $code)->first();
        }
        return view('admin.certificates.scan', compact('certificate'));
    }
    public function redeem(Request $request, Certificate $certificate)
    {
        abort_unless($certificate->isUsable(), 422, 'Сертификат недействителен, просрочен или уже использован.');
        DB::transaction(function () use ($request, $certificate) {
            $certificate->update(['status' => 'used', 'redeemed_at' => now(), 'redeemed_by' => $request->user()->id]);
            if ($certificate->customer_id) $certificate->customer->visits()->create(['visited_at' => now(), 'guests' => 1, 'source' => 'certificate', 'notes' => 'Проход по сертификату '.$certificate->serial]);
        });
        return back()->with('success', 'Сертификат погашен, проход зарегистрирован.');
    }
    private function serial(): string
    {
        do $serial = 'GC-'.now()->format('ymd').'-'.Str::upper(Str::random(6)); while (Certificate::where('serial', $serial)->exists());
        return $serial;
    }
}
