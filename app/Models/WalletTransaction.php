<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class WalletTransaction extends Model { use HasFactory; protected $fillable=['customer_wallet_id','wallet_type','direction','amount','source_type','source_id','description','created_by']; protected $casts=['amount'=>'decimal:2']; public function wallet(){return $this->belongsTo(CustomerWallet::class,'customer_wallet_id');} }
