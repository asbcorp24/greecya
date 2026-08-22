<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PoolZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index()
    {
        return view('admin.inventory.index',[
            'items'=>InventoryItem::with(['batches'=>fn($q)=>$q->where('remaining_qty','>',0)])->orderBy('name')->get(),
            'movements'=>InventoryMovement::with(['item','batch','zone'])->latest('occurred_at')->limit(150)->get(),
            'expiring'=>InventoryBatch::with('item')->where('remaining_qty','>',0)->whereNotNull('expires_on')->whereDate('expires_on','<=',today()->addDays(90))->orderBy('expires_on')->get(),
            'zones'=>PoolZone::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d=$request->validate(['sku'=>'required|string|max:100|unique:inventory_items,sku','name'=>'required|string|max:190','category'=>'required|string|max:80','unit'=>'required|string|max:20','purchase_price'=>'required|numeric|min:0','sale_price'=>'required|numeric|min:0','stock_qty'=>'required|numeric|min:0','min_stock'=>'required|numeric|min:0']);
        InventoryItem::create($d+['track_marking'=>$request->boolean('track_marking'),'is_active'=>true]);
        return back()->with('success','Товар добавлен на склад.');
    }

    public function storeBatch(Request $request, InventoryItem $item)
    {
        $d=$request->validate([
            'batch_number'=>'required|string|max:120','supplier'=>'nullable|string|max:190','manufactured_on'=>'nullable|date','expires_on'=>'nullable|date',
            'received_qty'=>'required|numeric|min:0.001','unit_cost'=>'required|numeric|min:0','received_at'=>'required|date','document_number'=>'nullable|string|max:120',
        ]);
        DB::transaction(function()use($request,$item,$d){
            $locked=InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $batch=InventoryBatch::updateOrCreate(['inventory_item_id'=>$item->id,'batch_number'=>$d['batch_number']],$d+['remaining_qty'=>$d['received_qty'],'status'=>'active']);
            $locked->increment('stock_qty',(float)$d['received_qty']);
            InventoryMovement::create(['inventory_item_id'=>$item->id,'inventory_batch_id'=>$batch->id,'user_id'=>$request->user()->id,'type'=>'in','quantity'=>$d['received_qty'],'unit_cost'=>$d['unit_cost'],'occurred_at'=>$d['received_at'],'note'=>'Приход партии '.$d['batch_number'].($d['document_number']?' · '.$d['document_number']:'')]);
        });
        return back()->with('success','Партия принята на склад.');
    }

    public function movement(Request $request,InventoryItem $item)
    {
        $d=$request->validate([
            'type'=>['required',Rule::in(['out','sale','adjustment'])],'quantity'=>'required|numeric|not_in:0','inventory_batch_id'=>'nullable|exists:inventory_batches,id',
            'pool_zone_id'=>'nullable|exists:pool_zones,id','unit_cost'=>'nullable|numeric|min:0','note'=>'nullable|string|max:255',
        ]);
        DB::transaction(function()use($request,$item,$d){
            $locked=InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();$qty=(float)$d['quantity'];
            $delta=in_array($d['type'],['out','sale'],true)?-abs($qty):$qty;
            $next=(float)$locked->stock_qty+$delta;abort_if($next<0,422,'Недостаточно остатка на складе.');
            $batch=null;
            if($delta<0){
                if(!empty($d['inventory_batch_id']))$batch=InventoryBatch::whereKey($d['inventory_batch_id'])->where('inventory_item_id',$item->id)->lockForUpdate()->firstOrFail();
                if(!$batch)$batch=InventoryBatch::where('inventory_item_id',$item->id)->where('remaining_qty','>',0)->orderByRaw('CASE WHEN expires_on IS NULL THEN 1 ELSE 0 END')->orderBy('expires_on')->orderBy('received_at')->lockForUpdate()->first();
                if($batch){abort_if((float)$batch->remaining_qty<abs($delta),422,'В выбранной партии недостаточно остатка.');$batch->decrement('remaining_qty',abs($delta));}
            }elseif($delta>0 && !empty($d['inventory_batch_id'])){
                $batch=InventoryBatch::whereKey($d['inventory_batch_id'])->where('inventory_item_id',$item->id)->lockForUpdate()->firstOrFail();$batch->increment('remaining_qty',$delta);
            }
            $locked->update(['stock_qty'=>$next]);
            InventoryMovement::create(['inventory_item_id'=>$locked->id,'inventory_batch_id'=>$batch?->id,'pool_zone_id'=>$d['pool_zone_id']??null,'user_id'=>$request->user()->id,'type'=>$d['type'],'quantity'=>$delta,'unit_cost'=>$d['unit_cost']??$batch?->unit_cost??$locked->purchase_price,'occurred_at'=>now(),'note'=>$d['note']??null]);
        });
        return back()->with('success','Складское движение проведено.');
    }
}
