<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyConsent extends Model
{
    protected $fillable = ['family_id','guardian_customer_id','child_customer_id','type','status','signed_at','expires_on','document_path','notes'];
    protected $casts = ['signed_at'=>'datetime','expires_on'=>'date'];
    public function family(){ return $this->belongsTo(Family::class); }
    public function guardian(){ return $this->belongsTo(Customer::class, 'guardian_customer_id'); }
    public function child(){ return $this->belongsTo(Customer::class, 'child_customer_id'); }
}
