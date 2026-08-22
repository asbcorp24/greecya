<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CustomerDocument extends Model { use HasFactory; protected $fillable=['customer_id','document_template_id','type','number','status','sign_method','signed_at','content']; protected $casts=['signed_at'=>'datetime']; public function customer(){return $this->belongsTo(Customer::class);} public function template(){return $this->belongsTo(DocumentTemplate::class,'document_template_id');} }
