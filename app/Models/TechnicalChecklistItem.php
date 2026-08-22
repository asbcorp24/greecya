<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalChecklistItem extends Model
{
    protected $fillable = ['technical_checklist_id','title','sort_order','required'];
    protected $casts = ['required'=>'boolean'];
    public function checklist(){ return $this->belongsTo(TechnicalChecklist::class, 'technical_checklist_id'); }
}
