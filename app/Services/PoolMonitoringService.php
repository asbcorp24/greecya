<?php

namespace App\Services;

use App\Models\PoolAlert;
use App\Models\PoolNorm;
use App\Models\PoolWaterLog;

class PoolMonitoringService
{
    public function record(array $data, ?int $userId): PoolWaterLog
    {
        $log = PoolWaterLog::create($data + ['user_id'=>$userId]);
        $norm = PoolNorm::where('pool_zone_id',$log->pool_zone_id)->first();
        if (!$norm || !$norm->alerts_enabled) return $log;

        $checks = [
            'temperature'=>[$norm->temperature_min,$norm->temperature_max],
            'ph'=>[$norm->ph_min,$norm->ph_max],
            'free_chlorine'=>[$norm->free_chlorine_min,$norm->free_chlorine_max],
            'redox'=>[$norm->redox_min,$norm->redox_max],
            'turbidity'=>[null,$norm->turbidity_max],
        ];

        foreach ($checks as $parameter => [$min,$max]) {
            $value = $log->{$parameter};
            if ($value === null) continue;
            $value = (float)$value;
            $outside = ($min !== null && $value < (float)$min) || ($max !== null && $value > (float)$max);
            if (!$outside) continue;

            $range = ($min !== null ? $min : '—').'…'.($max !== null ? $max : '—');
            $severity = $this->severity($value,$min,$max);
            PoolAlert::create([
                'pool_zone_id'=>$log->pool_zone_id,
                'pool_water_log_id'=>$log->id,
                'parameter'=>$parameter,
                'severity'=>$severity,
                'actual_value'=>$value,
                'expected_range'=>$range,
                'status'=>'open',
            ]);
        }
        return $log;
    }

    private function severity(float $value, $min, $max): string
    {
        if ($min !== null && (float)$min > 0 && $value < (float)$min * 0.8) return 'critical';
        if ($max !== null && (float)$max > 0 && $value > (float)$max * 1.2) return 'critical';
        return 'warning';
    }
}
