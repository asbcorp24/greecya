<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyWalletTransaction extends Model
{
    protected $fillable = ['family_wallet_id','customer_id','created_by','wallet_type','direction','amount','description'];
    protected $casts = ['amount'=>'decimal:2'];
    public function wallet(){ return $this->belongsTo(FamilyWallet::class, 'family_wallet_id'); }
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function creator(){ return $this->belongsTo(User::class, 'created_by'); }
}
