<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalChecklistResult extends Model
{
    protected $fillable = ['technical_checklist_run_id','technical_checklist_item_id','result','comment'];
    public function run(){ return $this->belongsTo(TechnicalChecklistRun::class, 'technical_checklist_run_id'); }
    public function item(){ return $this->belongsTo(TechnicalChecklistItem::class, 'technical_checklist_item_id'); }
}
