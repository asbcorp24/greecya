<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MembershipFreeze extends Model { use HasFactory; protected $fillable=['membership_id','starts_on','ends_on','days','reason','status','created_by']; protected $casts=['starts_on'=>'date','ends_on'=>'date']; public function membership(){return $this->belongsTo(Membership::class);} }
