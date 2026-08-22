<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MembershipPlan extends Model { use HasFactory; protected $fillable=['product_id','name','code','type','duration_days','visits_included','price','freeze_days','guest_visits','access_from','access_to','allowed_weekdays','is_active']; protected $casts=['price'=>'decimal:2','allowed_weekdays'=>'array','is_active'=>'boolean']; public function memberships(){return $this->hasMany(Membership::class);} public function product(){return $this->belongsTo(Product::class);} }
