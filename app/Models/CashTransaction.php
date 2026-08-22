<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CashTransaction extends Model { use HasFactory; protected $fillable=['cash_shift_id','user_id','customer_id','order_id','type','method','amount','description','occurred_at']; protected $casts=['amount'=>'decimal:2','occurred_at'=>'datetime']; public function shift(){return $this->belongsTo(CashShift::class,'cash_shift_id');} public function customer(){return $this->belongsTo(Customer::class);} }
