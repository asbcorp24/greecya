<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','target_type','service_id','product_id','customer_segment','weekdays','time_from','time_to',
        'occupancy_min_pct','occupancy_max_pct','starts_on','ends_on','adjustment_type','adjustment_value',
        'min_price','max_price','priority','combinable','is_active',
    ];

    protected $casts = [
        'weekdays'=>'array','occupancy_min_pct'=>'decimal:2','occupancy_max_pct'=>'decimal:2','starts_on'=>'date','ends_on'=>'date',
        'adjustment_value'=>'decimal:2','min_price'=>'decimal:2','max_price'=>'decimal:2','combinable'=>'boolean','is_active'=>'boolean',
    ];

    public function service(){ return $this->belongsTo(Service::class); }
    public function product(){ return $this->belongsTo(Product::class); }
}
