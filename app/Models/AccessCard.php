<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AccessCard extends Model { use HasFactory; protected $fillable=['customer_id','code','type','status','issued_at','expires_at']; protected $casts=['issued_at'=>'datetime','expires_at'=>'datetime']; public function customer(){return $this->belongsTo(Customer::class);} public function events(){return $this->hasMany(AccessEvent::class);} }
