<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGoal extends Model
{
    protected $fillable = ['customer_id','trainer_id','title','description','status','target_date','progress_percent'];
    protected $casts = ['target_date'=>'date'];
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function trainer(){ return $this->belongsTo(Trainer::class); }
}
