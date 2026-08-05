<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingPlanItem extends Model
{
    use HasFactory;
    protected $fillable = ['training_plan_id', 'day_label', 'exercise', 'sets', 'reps', 'duration_minutes', 'distance_meters', 'notes', 'sort_order'];
    public function plan() { return $this->belongsTo(TrainingPlan::class, 'training_plan_id'); }
}
