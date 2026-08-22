<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PayrollAccrual extends Model { use HasFactory; protected $fillable=['user_id','trainer_id','payroll_rule_id','period_month','quantity','amount','description','status','paid_at']; protected $casts=['period_month'=>'date','quantity'=>'decimal:2','amount'=>'decimal:2','paid_at'=>'datetime']; public function trainer(){return $this->belongsTo(Trainer::class);} public function rule(){return $this->belongsTo(PayrollRule::class,'payroll_rule_id');} }
