<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MedicalClearance extends Model { use HasFactory; protected $fillable=['customer_id','type','issued_on','expires_on','document_path','status','notes']; protected $casts=['issued_on'=>'date','expires_on'=>'date']; public function customer(){return $this->belongsTo(Customer::class);} public function isValid():bool{return $this->status==='valid'&&(!$this->expires_on||$this->expires_on->gte(today()));} }
