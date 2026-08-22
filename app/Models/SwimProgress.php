<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwimProgress extends Model
{
    protected $table = 'swim_progress';
    protected $fillable = ['swim_group_member_id','trainer_id','recorded_on','skill','score','comment'];
    protected $casts = ['recorded_on'=>'date'];
    public function member(){ return $this->belongsTo(SwimGroupMember::class, 'swim_group_member_id'); }
    public function trainer(){ return $this->belongsTo(Trainer::class); }
}
