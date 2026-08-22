<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CustomerWallet extends Model { use HasFactory; protected $fillable=['customer_id','deposit_balance','bonus_balance','loyalty_level']; protected $casts=['deposit_balance'=>'decimal:2','bonus_balance'=>'decimal:2']; public function customer(){return $this->belongsTo(Customer::class);} public function transactions(){return $this->hasMany(WalletTransaction::class)->latest();} }
