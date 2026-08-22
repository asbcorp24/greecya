<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id','name','code','type','audience_type','duration_days','visits_included','weekly_visit_limit','price','freeze_days','guest_visits',
        'access_from','access_to','allowed_weekdays','allowed_service_ids','allowed_pool_zone_ids','family_member_limit','corporate_required','personal_trainer_id',
        'requires_medical_clearance','is_active',
    ];

    protected $casts = [
        'price'=>'decimal:2','allowed_weekdays'=>'array','allowed_service_ids'=>'array','allowed_pool_zone_ids'=>'array',
        'corporate_required'=>'boolean','requires_medical_clearance'=>'boolean','is_active'=>'boolean',
    ];

    public function memberships(){ return $this->hasMany(Membership::class); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function personalTrainer(){ return $this->belongsTo(Trainer::class, 'personal_trainer_id'); }

    public function allowsWeekday(int $isoWeekday): bool
    {
        return empty($this->allowed_weekdays) || in_array($isoWeekday, array_map('intval', $this->allowed_weekdays), true);
    }

    public function allowsService(?int $serviceId): bool
    {
        return empty($this->allowed_service_ids) || ($serviceId && in_array($serviceId, array_map('intval', $this->allowed_service_ids), true));
    }

    public function allowsZone(?int $zoneId): bool
    {
        return empty($this->allowed_pool_zone_ids) || ($zoneId && in_array($zoneId, array_map('intval', $this->allowed_pool_zone_ids), true));
    }
}
