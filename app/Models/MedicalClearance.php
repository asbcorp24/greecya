<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id','type','doctor_name','organization','issued_on','expires_on','document_path','status','restrictions','contraindications',
        'access_blocked','blocked_reason','verified_by','verified_at','notes',
    ];

    protected $casts = ['issued_on'=>'date','expires_on'=>'date','access_blocked'=>'boolean','verified_at'=>'datetime'];

    public function customer(){ return $this->belongsTo(Customer::class); }
    public function verifier(){ return $this->belongsTo(User::class, 'verified_by'); }
    public function history(){ return $this->hasMany(MedicalClearanceHistory::class)->latest('changed_at'); }

    public function isValid(): bool
    {
        return $this->status === 'valid'
            && ! $this->access_blocked
            && (! $this->expires_on || $this->expires_on->gte(today()));
    }
}
