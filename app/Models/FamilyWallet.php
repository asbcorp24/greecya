<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyWallet extends Model
{
    protected $fillable = ['family_id','deposit_balance','bonus_balance'];
    protected $casts = ['deposit_balance'=>'decimal:2','bonus_balance'=>'decimal:2'];
    public function family(){ return $this->belongsTo(Family::class); }
    public function transactions(){ return $this->hasMany(FamilyWalletTransaction::class)->latest(); }
}
