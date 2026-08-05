<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgressEntry extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id', 'training_plan_id', 'recorded_on', 'weight', 'distance_meters', 'duration_seconds', 'note', 'coach_comment'];
    protected $casts = ['recorded_on' => 'date', 'weight' => 'decimal:2'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function plan() { return $this->belongsTo(TrainingPlan::class, 'training_plan_id'); }
}
