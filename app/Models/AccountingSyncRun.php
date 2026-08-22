<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingSyncRun extends Model
{
    use HasFactory;

    protected $fillable = ['accounting_integration_id','user_id','direction','format','status','period_from','period_to','record_counts','http_status','checksum','error_text','started_at','finished_at'];
    protected $casts = ['period_from'=>'datetime','period_to'=>'datetime','record_counts'=>'array','started_at'=>'datetime','finished_at'=>'datetime'];

    public function integration(){ return $this->belongsTo(AccountingIntegration::class, 'accounting_integration_id'); }
    public function user(){ return $this->belongsTo(User::class); }
}
