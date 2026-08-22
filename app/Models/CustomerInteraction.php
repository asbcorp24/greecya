<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CustomerInteraction extends Model { use HasFactory; protected $fillable=['customer_id','lead_id','user_id','channel','direction','subject','body','occurred_at']; protected $casts=['occurred_at'=>'datetime']; public function customer(){return $this->belongsTo(Customer::class);} public function lead(){return $this->belongsTo(Lead::class);} }
