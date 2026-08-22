<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CorporateMember extends Model { use HasFactory; protected $fillable=['corporate_account_id','customer_id','employee_number','status']; public function account(){return $this->belongsTo(CorporateAccount::class,'corporate_account_id');} public function customer(){return $this->belongsTo(Customer::class);} }
