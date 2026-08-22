<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CashShift extends Model { use HasFactory; protected $fillable=['cash_register_id','opened_by','closed_by','opened_at','closed_at','opening_cash','closing_cash','status']; protected $casts=['opened_at'=>'datetime','closed_at'=>'datetime','opening_cash'=>'decimal:2','closing_cash'=>'decimal:2']; public function register(){return $this->belongsTo(CashRegister::class,'cash_register_id');} public function transactions(){return $this->hasMany(CashTransaction::class);} }
