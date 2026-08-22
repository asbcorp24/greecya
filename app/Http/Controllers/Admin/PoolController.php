<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTask;
use App\Models\PoolLane;
use App\Models\PoolWaterLog;
use App\Models\PoolZone;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PoolController extends Controller
{
    public function index()
    {
        return view('admin.pool.index',[
            'zones'=>PoolZone::with(['lanes','waterLogs'=>fn($q)=>$q->limit(10)])->orderBy('name')->get(),
            'slots'=>ScheduleSlot::with(['service','trainer','zone','lanes'])->whereBetween('starts_at',[today(),now()->addDays(7)->endOfDay()])->orderBy('starts_at')->get(),
            'maintenance'=>MaintenanceTask::with(['zone','lane'])->orderByRaw("CASE WHEN status='open' THEN 0 ELSE 1 END")->orderBy('due_at')->limit(50)->get(),
        ]);
    }
    public function storeZone(Request $request){$d=$request->validate(['name'=>'required|string|max:190','code'=>'required|string|max:60|unique:pool_zones,code','type'=>'required|string|max:50','capacity'=>'required|integer|min:1|max:1000']);$d['is_active']=$request->boolean('is_active');PoolZone::create($d);return back()->with('success','Зона бассейна создана.');}
    public function storeLane(Request $request){$d=$request->validate(['pool_zone_id'=>'required|exists:pool_zones,id','name'=>'required|string|max:100','number'=>'required|integer|min:1|max:100','length_meters'=>'required|numeric|min:1|max:100','capacity'=>'required|integer|min:1|max:100']);$d['status']='open';$d['is_active']=true;PoolLane::create($d);return back()->with('success','Дорожка добавлена.');}
    public function updateLane(Request $request,PoolLane $lane){$d=$request->validate(['status'=>['required',Rule::in(['open','reserved','maintenance','closed'])],'capacity'=>'required|integer|min:1|max:100']);$lane->update($d+['is_active'=>$request->boolean('is_active')]);return back()->with('success','Дорожка обновлена.');}
    public function assignLane(Request $request,ScheduleSlot $slot){$d=$request->validate(['pool_lane_id'=>'required|exists:pool_lanes,id','capacity'=>'nullable|integer|min:1|max:100']);$slot->lanes()->syncWithoutDetaching([$d['pool_lane_id']=>['capacity'=>$d['capacity']??null]]);return back()->with('success','Дорожка назначена на сеанс.');}
    public function detachLane(ScheduleSlot $slot,PoolLane $lane){$slot->lanes()->detach($lane->id);return back()->with('success','Дорожка снята с сеанса.');}
    public function storeWater(Request $request){$d=$request->validate(['pool_zone_id'=>'required|exists:pool_zones,id','measured_at'=>'required|date','temperature'=>'nullable|numeric|min:0|max:50','ph'=>'nullable|numeric|min:0|max:14','free_chlorine'=>'nullable|numeric|min:0|max:20','redox'=>'nullable|numeric|min:0|max:1500','turbidity'=>'nullable|numeric|min:0|max:100','notes'=>'nullable|string|max:3000']);PoolWaterLog::create($d+['user_id'=>$request->user()->id]);return back()->with('success','Показатели воды сохранены.');}
    public function storeMaintenance(Request $request){$d=$request->validate(['pool_zone_id'=>'nullable|exists:pool_zones,id','pool_lane_id'=>'nullable|exists:pool_lanes,id','title'=>'required|string|max:190','type'=>'required|string|max:80','due_at'=>'nullable|date','notes'=>'nullable|string|max:3000']);MaintenanceTask::create($d+['assigned_to'=>$request->user()->id,'status'=>'open']);return back()->with('success','Задача техобслуживания создана.');}
    public function updateMaintenance(Request $request,MaintenanceTask $task){$d=$request->validate(['status'=>['required',Rule::in(['open','in_progress','completed','cancelled'])],'notes'=>'nullable|string|max:3000']);$d['completed_at']=$d['status']==='completed'?now():null;$task->update($d);return back()->with('success','Задача обновлена.');}
}
