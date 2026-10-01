<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','specialization','phone','bio','photo_path','experience_years','sort_order','is_active'];
    protected $casts = ['is_active'=>'boolean'];

    public function user(){ return $this->hasOne(User::class); }
    public function slots(){ return $this->hasMany(ScheduleSlot::class); }
    public function photos(){ return $this->hasMany(TrainerPhoto::class)->orderBy('sort_order')->orderBy('id'); }
    public function publicPhotos(){ return $this->photos()->where('is_active', true); }
    public function trainingPlans(){ return $this->hasMany(TrainingPlan::class); }
    public function swimGroups(){ return $this->hasMany(SwimGroup::class); }
    public function payrollAccruals(){ return $this->hasMany(PayrollAccrual::class); }
}
