<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(){return view('admin.inventory.index',['items'=>InventoryItem::orderBy('name')->get(),'movements'=>InventoryMovement::with('item')->latest('occurred_at')->limit(100)->get()]);}
    public function store(Request $request){$d=$request->validate(['sku'=>'required|string|max:100|unique:inventory_items,sku','name'=>'required|string|max:190','category'=>'required|string|max:80','unit'=>'required|string|max:20','purchase_price'=>'required|numeric|min:0','sale_price'=>'required|numeric|min:0','stock_qty'=>'required|numeric|min:0','min_stock'=>'required|numeric|min:0']);InventoryItem::create($d+['track_marking'=>$request->boolean('track_marking'),'is_active'=>true]);return back()->with('success','Товар добавлен на склад.');}
    public function movement(Request $request,InventoryItem $item){$d=$request->validate(['type'=>['required',Rule::in(['in','out','sale','adjustment'])],'quantity'=>'required|numeric|not_in:0','unit_cost'=>'nullable|numeric|min:0','note'=>'nullable|string|max:255']);DB::transaction(function()use($request,$item,$d){$locked=InventoryItem::whereKey($item->id)->lockForUpdate()->first();$qty=(float)$d['quantity'];$delta=$d['type']==='in'?abs($qty):(in_array($d['type'],['out','sale'],true)?-abs($qty):$qty);$next=(float)$locked->stock_qty+$delta;if($next<0)abort(422,'Недостаточно остатка на складе.');$locked->update(['stock_qty'=>$next]);InventoryMovement::create(['inventory_item_id'=>$locked->id,'user_id'=>$request->user()->id,'type'=>$d['type'],'quantity'=>$delta,'unit_cost'=>$d['unit_cost']??$locked->purchase_price,'occurred_at'=>now(),'note'=>$d['note']??null]);});return back()->with('success','Складское движение проведено.');}
}
