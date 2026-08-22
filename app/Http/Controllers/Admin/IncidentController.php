<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PoolLane;
use App\Models\PoolZone;
use App\Models\SafetyIncident;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $incidents=SafetyIncident::with(['customer','zone','lane','responsible'])
            ->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))
            ->when($request->filled('type'),fn($q)=>$q->where('type',$request->string('type')))
            ->latest('occurred_at')->paginate(40)->withQueryString();
        return view('admin.incidents.index',[
            'incidents'=>$incidents,'customers'=>Customer::orderBy('name')->get(['id','name','phone']),
            'zones'=>PoolZone::with('lanes')->orderBy('name')->get(),'users'=>User::where('role','!=','customer')->orderBy('name')->get(['id','name','role']),
        ]);
    }

    public function store(Request $request)
    {
        $d=$request->validate([
            'type'=>['required',Rule::in(['injury','ambulance','technical','complaint','lane_closure','security','other'])],
            'severity'=>['required',Rule::in(['low','medium','high','critical'])],
            'customer_id'=>'nullable|exists:customers,id','pool_zone_id'=>'nullable|exists:pool_zones,id','pool_lane_id'=>'nullable|exists:pool_lanes,id',
            'responsible_user_id'=>'nullable|exists:users,id','occurred_at'=>'required|date','description'=>'required|string|max:5000','actions_taken'=>'nullable|string|max:5000','photo'=>'nullable|image|max:8192',
        ]);
        $photo=$request->file('photo')?->store('incidents','public');
        $incident=SafetyIncident::create($d+[
            'number'=>$this->number(),'photo_path'=>$photo,'ambulance_called'=>$request->boolean('ambulance_called'),'lane_closed'=>$request->boolean('lane_closed'),'status'=>'open',
        ]);
        if($incident->lane_closed && $incident->pool_lane_id){
            PoolLane::whereKey($incident->pool_lane_id)->update(['status'=>'closed']);
        }
        return back()->with('success','Инцидент зарегистрирован под номером '.$incident->number.'.');
    }

    public function update(Request $request, SafetyIncident $incident)
    {
        $d=$request->validate(['status'=>['required',Rule::in(['open','investigating','resolved','closed'])],'responsible_user_id'=>'nullable|exists:users,id','actions_taken'=>'nullable|string|max:5000','resolution'=>'nullable|string|max:5000']);
        $d['closed_at']=in_array($d['status'],['resolved','closed'],true)?now():null;
        $incident->update($d);
        return back()->with('success','Статус расследования обновлён.');
    }

    private function number(): string
    {
        do{$n='INC-'.now()->format('ymd').'-'.Str::upper(Str::random(5));}while(SafetyIncident::where('number',$n)->exists());
        return $n;
    }
}
