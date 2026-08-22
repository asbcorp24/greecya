<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PoolLane;
use App\Models\PoolZone;
use App\Models\SwimAttendance;
use App\Models\SwimGroup;
use App\Models\SwimGroupMember;
use App\Models\SwimGroupSession;
use App\Models\SwimMakeup;
use App\Models\SwimProgress;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SwimSchoolController extends Controller
{
    public function index(Request $request)
    {
        $groups=SwimGroup::with(['trainer','zone','lane'])->withCount(['members'=>fn($q)=>$q->where('status','active')])
            ->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))
            ->orderBy('name')->get();
        return view('admin.swim-school.index',[
            'groups'=>$groups,
            'trainers'=>Trainer::where('is_active',true)->orderBy('name')->get(),
            'zones'=>PoolZone::where('is_active',true)->with('lanes')->orderBy('name')->get(),
        ]);
    }

    public function show(SwimGroup $group)
    {
        $group->load([
            'trainer','zone','lane',
            'members'=>fn($q)=>$q->with(['customer.medicalClearances','guardian','progress'])->orderBy('status')->orderBy('joined_on'),
            'sessions'=>fn($q)=>$q->with(['lane','attendance.member.customer'])->latest('starts_at')->limit(30),
        ]);
        return view('admin.swim-school.show',[
            'group'=>$group,
            'customers'=>Customer::orderBy('name')->get(['id','name','phone','birth_date']),
            'sessionsForMakeup'=>SwimGroupSession::where('swim_group_id',$group->id)->where('starts_at','>=',now())->orderBy('starts_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d=$request->validate([
            'name'=>'required|string|max:190','code'=>'required|string|max:80|unique:swim_groups,code','age_min'=>'nullable|integer|min:1|max:18','age_max'=>'nullable|integer|min:1|max:18',
            'level'=>'nullable|string|max:80','trainer_id'=>'nullable|exists:trainers,id','pool_zone_id'=>'nullable|exists:pool_zones,id','pool_lane_id'=>'nullable|exists:pool_lanes,id',
            'season_start'=>'nullable|date','season_end'=>'nullable|date|after_or_equal:season_start','max_members'=>'required|integer|min:1|max:100','notes'=>'nullable|string|max:3000',
        ]);
        SwimGroup::create($d+['status'=>'active']);
        return back()->with('success','Группа школы плавания создана.');
    }

    public function addMember(Request $request, SwimGroup $group)
    {
        $d=$request->validate(['customer_id'=>'required|exists:customers,id','guardian_customer_id'=>'nullable|exists:customers,id','joined_on'=>'nullable|date','notes'=>'nullable|string|max:2000']);
        $active=$group->members()->where('status','active')->count();
        abort_if($active >= $group->max_members,422,'Группа уже заполнена.');
        $customer=Customer::findOrFail($d['customer_id']);
        if($customer->birth_date){
            $age=$customer->birth_date->age;
            if($group->age_min!==null && $age<$group->age_min) return back()->withErrors(['customer_id'=>'Возраст ребёнка ниже минимального для группы.']);
            if($group->age_max!==null && $age>$group->age_max) return back()->withErrors(['customer_id'=>'Возраст ребёнка выше максимального для группы.']);
        }
        SwimGroupMember::updateOrCreate(['swim_group_id'=>$group->id,'customer_id'=>$customer->id],[
            'guardian_customer_id'=>$d['guardian_customer_id']??null,'joined_on'=>$d['joined_on']??today(),'left_on'=>null,'status'=>'active','notes'=>$d['notes']??null,
        ]);
        return back()->with('success','Ученик добавлен в группу.');
    }

    public function updateMember(Request $request, SwimGroup $group, SwimGroupMember $member)
    {
        abort_unless((int)$member->swim_group_id===(int)$group->id,404);
        $d=$request->validate(['status'=>['required',Rule::in(['active','paused','left'])],'notes'=>'nullable|string|max:2000']);
        $d['left_on']=$d['status']==='left'?today():null;
        $member->update($d);
        return back()->with('success','Статус ученика обновлён.');
    }

    public function storeSession(Request $request, SwimGroup $group)
    {
        $d=$request->validate(['starts_at'=>'required|date','ends_at'=>'required|date|after:starts_at','pool_lane_id'=>'nullable|exists:pool_lanes,id','notes'=>'nullable|string|max:2000']);
        SwimGroupSession::create($d+['swim_group_id'=>$group->id,'pool_lane_id'=>$d['pool_lane_id']??$group->pool_lane_id,'status'=>'scheduled']);
        return back()->with('success','Занятие добавлено в сезонный график.');
    }

    public function attendance(Request $request, SwimGroup $group, SwimGroupSession $session)
    {
        abort_unless((int)$session->swim_group_id===(int)$group->id,404);
        $data=$request->validate(['status'=>'array','status.*'=>['nullable',Rule::in(['present','absent','excused','makeup'])],'notes'=>'array']);
        DB::transaction(function()use($request,$group,$session,$data){
            foreach($group->members()->where('status','active')->get() as $member){
                $status=$data['status'][$member->id]??null;
                if(!$status) continue;
                $attendance=SwimAttendance::updateOrCreate([
                    'swim_group_session_id'=>$session->id,'swim_group_member_id'=>$member->id,
                ],[
                    'status'=>$status,'checkin_at'=>in_array($status,['present','makeup'],true)?now():null,'notes'=>$data['notes'][$member->id]??null,'marked_by'=>$request->user()->id,
                ]);
                if($status==='excused'){
                    SwimMakeup::firstOrCreate([
                        'swim_group_member_id'=>$member->id,'missed_session_id'=>$session->id,
                    ],['status'=>'available','expires_on'=>today()->addMonth(),'notes'=>'Автоматически создана отработка уважительного пропуска.']);
                }
            }
            $session->update(['status'=>'completed']);
        });
        return back()->with('success','Посещаемость сохранена.');
    }

    public function assignMakeup(Request $request, SwimGroup $group, SwimMakeup $makeup)
    {
        abort_unless((int)$makeup->member->swim_group_id===(int)$group->id,404);
        $d=$request->validate(['makeup_session_id'=>'required|exists:swim_group_sessions,id']);
        $target=SwimGroupSession::whereKey($d['makeup_session_id'])->where('swim_group_id',$group->id)->firstOrFail();
        $makeup->update(['makeup_session_id'=>$target->id,'status'=>'scheduled']);
        return back()->with('success','Отработка назначена.');
    }

    public function progress(Request $request, SwimGroup $group, SwimGroupMember $member)
    {
        abort_unless((int)$member->swim_group_id===(int)$group->id,404);
        $d=$request->validate(['recorded_on'=>'required|date','skill'=>'required|string|max:190','score'=>'nullable|integer|min:1|max:5','comment'=>'nullable|string|max:3000']);
        SwimProgress::create($d+['swim_group_member_id'=>$member->id,'trainer_id'=>$group->trainer_id]);
        return back()->with('success','Прогресс ученика сохранён.');
    }
}
