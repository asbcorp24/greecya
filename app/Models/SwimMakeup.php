<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwimMakeup extends Model
{
    protected $fillable = ['swim_group_member_id','missed_session_id','makeup_session_id','status','expires_on','notes'];
    protected $casts = ['expires_on'=>'date'];
    public function member(){ return $this->belongsTo(SwimGroupMember::class, 'swim_group_member_id'); }
    public function missedSession(){ return $this->belongsTo(SwimGroupSession::class, 'missed_session_id'); }
    public function makeupSession(){ return $this->belongsTo(SwimGroupSession::class, 'makeup_session_id'); }
}
