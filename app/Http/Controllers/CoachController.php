<?php

namespace App\Http\Controllers;

use App\Models\CustomerNote;
use App\Models\PayrollAccrual;
use App\Models\ScheduleSlot;
use App\Models\SwimAttendance;
use App\Models\SwimGroup;
use App\Models\SwimGroupMember;
use App\Models\SwimGroupSession;
use App\Models\SwimMakeup;
use App\Models\SwimProgress;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CoachController extends Controller
{
    public function index(Request $request)
    {
        $trainer=$request->user()->trainer;
        abort_unless($trainer,403,'Учётная запись не привязана к карточке тренера.');

        $slots=ScheduleSlot::with(['service','zone','lanes','bookings.customer'])
            ->where('trainer_id',$trainer->id)
            ->whereBetween('starts_at',[now()->startOfDay(),now()->addDays(14)->endOfDay()])
            ->orderBy('starts_at')->get();
        $groups=SwimGroup::with(['lane','zone','members.customer','sessions'=>fn($q)=>$q->where('starts_at','>=',today())->orderBy('starts_at')->limit(8)])
            ->where('trainer_id',$trainer->id)->where('status','active')->orderBy('name')->get();
        $plans=TrainingPlan::with(['customer','items','progressEntries'])->where('trainer_id',$trainer->id)->where('status','active')->orderBy('customer_id')->get();
        $payroll=PayrollAccrual::where('trainer_id',$trainer->id)->whereDate('period_month','>=',today()->startOfYear())->orderByDesc('period_month')->get();

        $customers=$slots->flatMap(fn($slot)=>$slot->bookings->pluck('customer'))
            ->merge($groups->flatMap(fn($g)=>$g->members->pluck('customer')))
            ->merge($plans->pluck('customer'))->filter()->unique('id')->sortBy('name')->values();

        return view('coach.index',compact('trainer','slots','groups','plans','payroll','customers'));
    }

    public function note(Request $request)
    {
        $trainer=$request->user()->trainer;abort_unless($trainer,403);
        $d=$request->validate(['customer_id'=>'required|exists:customers,id','body'=>'required|string|max:5000']);
        $allowed=$this->customerBelongsToTrainer($trainer->id,(int)$d['customer_id']);
        abort_unless($allowed,403);
        CustomerNote::create(['customer_id'=>$d['customer_id'],'user_id'=>$request->user()->id,'type'=>'trainer','body'=>$d['body'],'is_private'=>true]);
        return back()->with('success','Заметка тренера добавлена.');
    }

    public function attendance(Request $request, SwimGroupSession $session)
    {
        $trainer=$request->user()->trainer;abort_unless($trainer,403);
        $session->load('group.members');
        abort_unless((int)$session->group->trainer_id===(int)$trainer->id,403);
        $data=$request->validate(['status'=>'array','status.*'=>['nullable',Rule::in(['present','absent','excused','makeup'])],'notes'=>'array']);
        DB::transaction(function()use($request,$session,$data){
            foreach($session->group->members()->where('status','active')->get() as $member){
                $status=$data['status'][$member->id]??null;if(!$status)continue;
                SwimAttendance::updateOrCreate(['swim_group_session_id'=>$session->id,'swim_group_member_id'=>$member->id],[
                    'status'=>$status,'checkin_at'=>in_array($status,['present','makeup'],true)?now():null,'notes'=>$data['notes'][$member->id]??null,'marked_by'=>$request->user()->id,
                ]);
                if($status==='excused')SwimMakeup::firstOrCreate(['swim_group_member_id'=>$member->id,'missed_session_id'=>$session->id],['status'=>'available','expires_on'=>today()->addMonth()]);
            }
            $session->update(['status'=>'completed']);
        });
        return back()->with('success','Посещаемость группы сохранена.');
    }

    public function progress(Request $request, SwimGroupMember $member)
    {
        $trainer=$request->user()->trainer;abort_unless($trainer,403);
        $member->load('group');abort_unless((int)$member->group->trainer_id===(int)$trainer->id,403);
        $d=$request->validate(['recorded_on'=>'required|date','skill'=>'required|string|max:190','score'=>'nullable|integer|min:1|max:5','comment'=>'nullable|string|max:3000']);
        SwimProgress::create($d+['swim_group_member_id'=>$member->id,'trainer_id'=>$trainer->id]);
        return back()->with('success','Прогресс ученика сохранён.');
    }

    private function customerBelongsToTrainer(int $trainerId,int $customerId): bool
    {
        return TrainingPlan::where('trainer_id',$trainerId)->where('customer_id',$customerId)->exists()
            || SwimGroupMember::where('customer_id',$customerId)->whereHas('group',fn($q)=>$q->where('trainer_id',$trainerId))->exists()
            || ScheduleSlot::where('trainer_id',$trainerId)->whereHas('bookings',fn($q)=>$q->where('customer_id',$customerId))->exists();
    }
}
