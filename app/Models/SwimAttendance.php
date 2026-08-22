<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwimAttendance extends Model
{
    protected $table = 'swim_attendance';
    protected $fillable = ['swim_group_session_id','swim_group_member_id','status','checkin_at','notes','marked_by'];
    protected $casts = ['checkin_at'=>'datetime'];
    public function session(){ return $this->belongsTo(SwimGroupSession::class, 'swim_group_session_id'); }
    public function member(){ return $this->belongsTo(SwimGroupMember::class, 'swim_group_member_id'); }
    public function marker(){ return $this->belongsTo(User::class, 'marked_by'); }
}
