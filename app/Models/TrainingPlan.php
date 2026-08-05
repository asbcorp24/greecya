<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingPlan extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id', 'trainer_id', 'title', 'goal', 'description', 'schedule_text', 'recommendations', 'starts_on', 'ends_on', 'status'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function trainer() { return $this->belongsTo(Trainer::class); }
    public function items() { return $this->hasMany(TrainingPlanItem::class)->orderBy('sort_order')->orderBy('id'); }
    public function progressEntries() { return $this->hasMany(TrainingProgressEntry::class)->latest('recorded_on'); }
}
