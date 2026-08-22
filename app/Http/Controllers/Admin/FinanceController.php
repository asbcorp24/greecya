<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function index(){return view('admin.finance.index',['registers'=>CashRegister::with(['shifts'=>fn($q)=>$q->latest()->limit(5)])->orderBy('name')->get(),'openShifts'=>CashShift::with('register')->where('status','open')->get(),'transactions'=>CashTransaction::with(['shift.register','customer'])->latest('occurred_at')->limit(100)->get(),'customers'=>Customer::orderBy('name')->get(['id','name','phone'])]);}
    public function storeRegister(Request $request){$d=$request->validate(['name'=>'required|string|max:190','code'=>'required|string|max:80|unique:cash_registers,code','location'=>'nullable|string|max:190']);CashRegister::create($d+['is_active'=>true]);return back()->with('success','Касса создана.');}
    public function openShift(Request $request,CashRegister $register){$d=$request->validate(['opening_cash'=>'required|numeric|min:0']);if($register->shifts()->where('status','open')->exists())return back()->withErrors(['shift'=>'По этой кассе уже открыта смена.']);CashShift::create(['cash_register_id'=>$register->id,'opened_by'=>$request->user()->id,'opened_at'=>now(),'opening_cash'=>$d['opening_cash'],'status'=>'open']);return back()->with('success','Кассовая смена открыта.');}
    public function closeShift(Request $request,CashShift $shift){abort_unless($shift->status==='open',422);$cash=(float)$shift->opening_cash;foreach($shift->transactions()->where('method','cash')->get() as $tx)$cash+=in_array($tx->type,['refund','expense'],true)?-(float)$tx->amount:(float)$tx->amount;$shift->update(['closed_by'=>$request->user()->id,'closed_at'=>now(),'closing_cash'=>$cash,'status'=>'closed']);return back()->with('success','Смена закрыта. Расчётный остаток: '.number_format($cash,2,',',' ').' ₽');}
    public function transaction(Request $request){$d=$request->validate(['cash_shift_id'=>'required|exists:cash_shifts,id','customer_id'=>'nullable|exists:customers,id','type'=>['required',Rule::in(['sale','refund','deposit','expense','withdrawal'])],'method'=>['required',Rule::in(['cash','card','sbp','bank'])],'amount'=>'required|numeric|min:0.01|max:10000000','description'=>'nullable|string|max:255']);$shift=CashShift::whereKey($d['cash_shift_id'])->where('status','open')->firstOrFail();CashTransaction::create($d+['user_id'=>$request->user()->id,'occurred_at'=>now()]);return back()->with('success','Кассовая операция зарегистрирована.');}
}
