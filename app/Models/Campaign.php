<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Campaign extends Model { use HasFactory; protected $fillable=['name','channel','subject','body','audience','status','scheduled_at','sent_at']; protected $casts=['audience'=>'array','scheduled_at'=>'datetime','sent_at'=>'datetime']; public function messages(){return $this->hasMany(MessageLog::class);} }
