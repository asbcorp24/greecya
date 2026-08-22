<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalChecklistRun extends Model
{
    protected $fillable = ['technical_checklist_id','user_id','performed_at','status','notes'];
    protected $casts = ['performed_at'=>'datetime'];
    public function checklist(){ return $this->belongsTo(TechnicalChecklist::class, 'technical_checklist_id'); }
    public function user(){ return $this->belongsTo(User::class); }
    public function results(){ return $this->hasMany(TechnicalChecklistResult::class); }
}
