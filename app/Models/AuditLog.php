<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id','action','route_name','method','subject_type','subject_id','before','after','metadata','ip_address','user_agent','created_at'];
    protected $casts = ['before'=>'array','after'=>'array','metadata'=>'array','created_at'=>'datetime'];

    public function user(){ return $this->belongsTo(User::class); }
}
