<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingController extends Controller
{
    public function index()
    {
        return view('admin.pricing.index', [
            'rules'=>PricingRule::with(['service','product'])->orderBy('priority')->orderBy('name')->get(),
            'services'=>Service::where('is_active',true)->orderBy('name')->get(),
            'products'=>Product::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        PricingRule::create($this->validated($request));
        return back()->with('success','Правило динамической цены создано.');
    }

    public function update(Request $request, PricingRule $pricingRule)
    {
        $pricingRule->update($this->validated($request));
        return back()->with('success','Правило цены обновлено.');
    }

    public function destroy(PricingRule $pricingRule)
    {
        $pricingRule->delete();
        return back()->with('success','Правило цены удалено.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'=>'required|string|max:190',
            'target_type'=>['required',Rule::in(['service','product'])],
            'service_id'=>'nullable|exists:services,id',
            'product_id'=>'nullable|exists:products,id',
            'customer_segment'=>['required',Rule::in(['all','child','senior','family','corporate'])],
            'weekdays'=>'nullable|array',
            'weekdays.*'=>'integer|min:1|max:7',
            'time_from'=>'nullable',
            'time_to'=>'nullable',
            'occupancy_min_pct'=>'nullable|numeric|min:0|max:100',
            'occupancy_max_pct'=>'nullable|numeric|min:0|max:100',
            'starts_on'=>'nullable|date',
            'ends_on'=>'nullable|date|after_or_equal:starts_on',
            'adjustment_type'=>['required',Rule::in(['percent','fixed','override'])],
            'adjustment_value'=>'required|numeric|min:-1000000|max:1000000',
            'min_price'=>'nullable|numeric|min:0',
            'max_price'=>'nullable|numeric|min:0',
            'priority'=>'required|integer|min:1|max:10000',
        ]);
        if ($data['target_type']==='service') $data['product_id']=null; else $data['service_id']=null;
        $data['combinable']=$request->boolean('combinable');
        $data['is_active']=$request->boolean('is_active');
        return $data;
    }
}
