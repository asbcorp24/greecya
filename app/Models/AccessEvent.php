<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessEvent extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id','access_card_id','pool_zone_id','visit_id','event_type','result','reason','occurred_at'];
    protected $casts = ['occurred_at'=>'datetime'];

    public function customer(){ return $this->belongsTo(Customer::class); }
    public function card(){ return $this->belongsTo(AccessCard::class,'access_card_id'); }
    public function zone(){ return $this->belongsTo(PoolZone::class,'pool_zone_id')->withTrashed(); }
}
