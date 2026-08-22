<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MedicalClearance;
use App\Models\MedicalClearanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicalController extends Controller
{
    public function index(Request $request)
    {
        $clearances=MedicalClearance::with(['customer','verifier'])
            ->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))
            ->when($request->boolean('expiring'),fn($q)=>$q->whereBetween('expires_on',[today(),today()->addDays(30)]))
            ->when($request->filled('q'),function($q)use($request){$s='%'.$request->string('q').'%';$q->whereHas('customer',fn($c)=>$c->where('name','like',$s)->orWhere('phone','like',$s));})
            ->latest()->paginate(40)->withQueryString();
        return view('admin.medical.index',['clearances'=>$clearances,'customers'=>Customer::orderBy('name')->get(['id','name','phone','birth_date'])]);
    }

    public function store(Request $request)
    {
        $d=$this->validateData($request);
        $path=$request->file('document')?->store('medical-clearances','public');
        DB::transaction(function()use($request,$d,$path){
            $clearance=MedicalClearance::create($d+[
                'document_path'=>$path,'verified_by'=>$request->user()->id,'verified_at'=>now(),
                'access_blocked'=>$request->boolean('access_blocked'),
            ]);
            MedicalClearanceHistory::create([
                'medical_clearance_id'=>$clearance->id,'user_id'=>$request->user()->id,'from_status'=>null,'to_status'=>$clearance->status,
                'access_blocked'=>$clearance->access_blocked,'reason'=>$clearance->blocked_reason ?: 'Документ создан','changed_at'=>now(),
            ]);
        });
        return back()->with('success','Медицинский документ добавлен.');
    }

    public function update(Request $request, MedicalClearance $clearance)
    {
        $d=$this->validateData($request,$clearance);
        $path=$request->file('document')?->store('medical-clearances','public');
        DB::transaction(function()use($request,$clearance,$d,$path){
            $from=$clearance->status;
            $payload=$d+[
                'verified_by'=>$request->user()->id,'verified_at'=>now(),'access_blocked'=>$request->boolean('access_blocked'),
            ];
            if($path)$payload['document_path']=$path;
            $clearance->update($payload);
            MedicalClearanceHistory::create([
                'medical_clearance_id'=>$clearance->id,'user_id'=>$request->user()->id,'from_status'=>$from,'to_status'=>$clearance->status,
                'access_blocked'=>$clearance->access_blocked,'reason'=>$clearance->blocked_reason ?: 'Документ проверен/обновлён','changed_at'=>now(),
            ]);
        });
        return back()->with('success','Медицинский допуск обновлён.');
    }

    public function history(MedicalClearance $clearance)
    {
        $clearance->load(['customer','history.user']);
        return view('admin.medical.history',compact('clearance'));
    }

    private function validateData(Request $request, ?MedicalClearance $clearance=null): array
    {
        return $request->validate([
            'customer_id'=>'required|exists:customers,id','type'=>'required|string|max:80','doctor_name'=>'nullable|string|max:190','organization'=>'nullable|string|max:190',
            'issued_on'=>'nullable|date','expires_on'=>'nullable|date','status'=>['required',Rule::in(['valid','pending','expired','revoked'])],
            'restrictions'=>'nullable|string|max:2000','contraindications'=>'nullable|string|max:2000','blocked_reason'=>'nullable|string|max:255','notes'=>'nullable|string|max:3000',
            'document'=>'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
    }
}
