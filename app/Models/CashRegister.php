<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CashRegister extends Model { use HasFactory; protected $fillable=['name','code','location','is_active']; protected $casts=['is_active'=>'boolean']; public function shifts(){return $this->hasMany(CashShift::class);} }
