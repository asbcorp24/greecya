<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Membership extends Model { use HasFactory; protected $fillable=['number','customer_id','membership_plan_id','order_item_id','status','starts_on','ends_on','visits_total','visits_used','freeze_days_total','freeze_days_used','guest_visits_left','auto_renew','price_paid','notes']; protected $casts=['starts_on'=>'date','ends_on'=>'date','auto_renew'=>'boolean','price_paid'=>'decimal:2']; public function customer(){return $this->belongsTo(Customer::class);} public function plan(){return $this->belongsTo(MembershipPlan::class,'membership_plan_id');} public function freezes(){return $this->hasMany(MembershipFreeze::class);} public function isUsable():bool{return $this->status==='active'&&$this->starts_on->lte(today())&&$this->ends_on->gte(today())&&($this->visits_total===null||$this->visits_used<$this->visits_total);} }
