<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CorporateAccount extends Model { use HasFactory; protected $fillable=['name','tax_id','contact_name','phone','email','discount_percent','credit_limit','status']; protected $casts=['discount_percent'=>'decimal:2','credit_limit'=>'decimal:2']; public function members(){return $this->hasMany(CorporateMember::class);} }
