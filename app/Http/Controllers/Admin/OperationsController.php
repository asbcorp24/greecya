<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChemicalUsage;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PoolAlert;
use App\Models\PoolNorm;
use App\Models\PoolOperation;
use App\Models\PoolWaterLog;
use App\Models\PoolZone;
use App\Models\TechnicalChecklist;
use App\Models\TechnicalChecklistItem;
use App\Models\TechnicalChecklistResult;
use App\Models\TechnicalChecklistRun;
use App\Services\PoolMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    public function index(Request $request)
    {
        $zoneId=$request->integer('zone') ?: PoolZone::where('type','pool')->value('id');
        $zones=PoolZone::with('lanes')->orderBy('name')->get();
        $readings=PoolWaterLog::with('zone')->when($zoneId,fn($q)=>$q->where('pool_zone_id',$zoneId))->latest('measured_at')->limit(100)->get()->reverse()->values();
        return view('admin.operations.index',[
            'zones'=>$zones,'selectedZone'=>$zoneId,'readings'=>$readings,
            'norms'=>PoolNorm::with('zone')->get()->keyBy('pool_zone_id'),
            'alerts'=>PoolAlert::with('zone')->orderByRaw("CASE WHEN status='open' THEN 0 ELSE 1 END")->latest()->limit(100)->get(),
            'operations'=>PoolOperation::with(['zone','lane','user'])->latest('performed_at')->limit(100)->get(),
            'checklists'=>TechnicalChecklist::with(['zone','items','runs.user'])->where('is_active',true)->orderBy('name')->get(),
            'chemicals'=>InventoryItem::with(['batches'=>fn($q)=>$q->where('remaining_qty','>',0)])->whereIn('category',['chemical','chemistry','reagent'])->where('is_active',true)->orderBy('name')->get(),
            'usages'=>ChemicalUsage::with(['zone','item','batch','user'])->latest('used_at')->limit(100)->get(),
        ]);
    }

    public function norm(Request $request, PoolZone $zone)
    {
        $d=$request->validate([
            'temperature_min'=>'nullable|numeric','temperature_max'=>'nullable|numeric','ph_min'=>'nullable|numeric|min:0|max:14','ph_max'=>'nullable|numeric|min:0|max:14',
            'free_chlorine_min'=>'nullable|numeric|min:0','free_chlorine_max'=>'nullable|numeric|min:0','redox_min'=>'nullable|numeric','redox_max'=>'nullable|numeric','turbidity_max'=>'nullable|numeric|min:0',
        ]);
        PoolNorm::updateOrCreate(['pool_zone_id'=>$zone->id],$d+['alerts_enabled'=>$request->boolean('alerts_enabled')]);
        return back()->with('success','Нормативные диапазоны обновлены.');
    }

    public function water(Request $request, PoolMonitoringService $monitoring)
    {
        $d=$request->validate(['pool_zone_id'=>'required|exists:pool_zones,id','measured_at'=>'required|date','temperature'=>'nullable|numeric|min:0|max:50','ph'=>'nullable|numeric|min:0|max:14','free_chlorine'=>'nullable|numeric|min:0|max:20','redox'=>'nullable|numeric|min:0|max:1500','turbidity'=>'nullable|numeric|min:0|max:100','notes'=>'nullable|string|max:3000']);
        $monitoring->record($d,$request->user()->id);
        return back()->with('success','Замер сохранён, нормативы проверены автоматически.');
    }

    public function acknowledge(Request $request, PoolAlert $alert)
    {
        $d=$request->validate(['status'=>['required',Rule::in(['acknowledged','resolved'])],'notes'=>'nullable|string|max:2000']);
        $alert->update($d+['acknowledged_by'=>$request->user()->id,'acknowledged_at'=>now()]);
        return back()->with('success','Предупреждение обработано.');
    }

    public function operation(Request $request)
    {
        $d=$request->validate(['pool_zone_id'=>'nullable|exists:pool_zones,id','pool_lane_id'=>'nullable|exists:pool_lanes,id','type'=>['required',Rule::in(['filter_backwash','reagent_addition','water_topup','cleaning','equipment_check','repair','shutdown','other'])],'performed_at'=>'required|date','duration_minutes'=>'nullable|numeric|min:0','details'=>'nullable|string|max:3000','result'=>'nullable|string|max:255']);
        PoolOperation::create($d+['user_id'=>$request->user()->id]);
        return back()->with('success','Эксплуатационная операция записана.');
    }

    public function checklist(Request $request)
    {
        $d=$request->validate(['name'=>'required|string|max:190','type'=>'required|string|max:80','pool_zone_id'=>'nullable|exists:pool_zones,id','items'=>'required|string|max:5000']);
        DB::transaction(function()use($d){
            $checklist=TechnicalChecklist::create(['name'=>$d['name'],'type'=>$d['type'],'pool_zone_id'=>$d['pool_zone_id']??null,'is_active'=>true]);
            $lines=preg_split('/\r\n|\r|\n/',$d['items']);$sort=10;
            foreach($lines as $line){$line=trim($line);if($line==='')continue;TechnicalChecklistItem::create(['technical_checklist_id'=>$checklist->id,'title'=>$line,'sort_order'=>$sort,'required'=>true]);$sort+=10;}
        });
        return back()->with('success','Технический чек-лист создан.');
    }

    public function runChecklist(Request $request, TechnicalChecklist $checklist)
    {
        $checklist->load('items');
        $data=$request->validate(['result'=>'array','comment'=>'array','notes'=>'nullable|string|max:3000']);
        DB::transaction(function()use($request,$checklist,$data){
            $run=TechnicalChecklistRun::create(['technical_checklist_id'=>$checklist->id,'user_id'=>$request->user()->id,'performed_at'=>now(),'status'=>'completed','notes'=>$data['notes']??null]);
            $hasIssue=false;
            foreach($checklist->items as $item){
                $result=$data['result'][$item->id]??'not_checked';
                if(!in_array($result,['ok','issue','not_checked'],true))$result='not_checked';
                if($result!=='ok')$hasIssue=true;
                TechnicalChecklistResult::create(['technical_checklist_run_id'=>$run->id,'technical_checklist_item_id'=>$item->id,'result'=>$result,'comment'=>$data['comment'][$item->id]??null]);
            }
            if($hasIssue)$run->update(['status'=>'attention']);
        });
        return back()->with('success','Чек-лист выполнен.');
    }

    public function chemicalUsage(Request $request)
    {
        $d=$request->validate(['pool_zone_id'=>'required|exists:pool_zones,id','inventory_item_id'=>'required|exists:inventory_items,id','inventory_batch_id'=>'nullable|exists:inventory_batches,id','quantity'=>'required|numeric|min:0.001','used_at'=>'required|date','purpose'=>'nullable|string|max:255','notes'=>'nullable|string|max:2000']);
        DB::transaction(function()use($request,$d){
            $item=InventoryItem::whereKey($d['inventory_item_id'])->lockForUpdate()->firstOrFail();
            $qty=(float)$d['quantity'];abort_if((float)$item->stock_qty<$qty,422,'Недостаточно реагента на складе.');
            $batch=null;
            if(!empty($d['inventory_batch_id']))$batch=InventoryBatch::whereKey($d['inventory_batch_id'])->where('inventory_item_id',$item->id)->lockForUpdate()->firstOrFail();
            if(!$batch)$batch=InventoryBatch::where('inventory_item_id',$item->id)->where('remaining_qty','>',0)->where(fn($q)=>$q->whereNull('expires_on')->orWhereDate('expires_on','>=',today()))->orderByRaw('CASE WHEN expires_on IS NULL THEN 1 ELSE 0 END')->orderBy('expires_on')->orderBy('received_at')->lockForUpdate()->first();
            if($batch){abort_if((float)$batch->remaining_qty<$qty,422,'В выбранной партии недостаточно остатка.');$batch->decrement('remaining_qty',$qty);}
            $item->decrement('stock_qty',$qty);
            InventoryMovement::create(['inventory_item_id'=>$item->id,'inventory_batch_id'=>$batch?->id,'pool_zone_id'=>$d['pool_zone_id'],'user_id'=>$request->user()->id,'type'=>'out','quantity'=>-$qty,'unit_cost'=>$batch?->unit_cost??$item->purchase_price,'occurred_at'=>$d['used_at'],'note'=>'Расход реагента: '.($d['purpose']??'обработка воды')]);
            ChemicalUsage::create($d+['inventory_batch_id'=>$batch?->id,'user_id'=>$request->user()->id,'unit'=>$item->unit]);
        });
        return back()->with('success','Расход реагента списан с партии и склада.');
    }
}
