<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CrmTask extends Model { use HasFactory; protected $fillable=['lead_id','customer_id','assigned_to','type','title','description','due_at','status','completed_at']; protected $casts=['due_at'=>'datetime','completed_at'=>'datetime']; public function customer(){return $this->belongsTo(Customer::class);} public function lead(){return $this->belongsTo(Lead::class);} public function assignee(){return $this->belongsTo(User::class,'assigned_to');} }
