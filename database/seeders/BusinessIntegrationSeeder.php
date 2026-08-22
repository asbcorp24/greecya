<?php

namespace Database\Seeders;

use App\Models\AccountingIntegration;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusinessIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email'=>'director@greecya.local'],[
            'name'=>'Директор комплекса','phone'=>'+7 900 888-88-88','role'=>'director','password'=>Hash::make('ChangeMe123!'),
        ]);

        AccountingIntegration::updateOrCreate(['name'=>'1С:Бухгалтерия'],[
            'driver'=>'json_http','organization_code'=>'GREECYA-DEMO','format_version'=>'1.23','options'=>['timeout'=>30],'is_active'=>false,
        ]);

        $rules = [
            ['Будни утром −20%','service','all',[1,2,3,4,5],'07:00','12:00',null,null,'percent',-20,20,true],
            ['Низкая загрузка −15%','service','all',null,null,null,null,30,'percent',-15,30,true],
            ['Вечерний пик +10%','service','all',[1,2,3,4,5],'18:00','22:00',null,null,'percent',10,40,true],
            ['Выходные +10%','service','all',[6,7],null,null,null,null,'percent',10,50,true],
            ['Детский тариф −15%','service','child',null,null,null,null,null,'percent',-15,60,true],
            ['Пенсионный тариф −15%','service','senior',null,null,null,null,null,'percent',-15,60,true],
            ['Семейная скидка −10%','service','family',null,null,null,null,null,'percent',-10,70,true],
            ['Семейная скидка на абонементы −5%','product','family',null,null,null,null,null,'percent',-5,70,true],
        ];

        foreach($rules as [$name,$target,$segment,$weekdays,$from,$to,$occMin,$occMax,$type,$value,$priority,$combinable]){
            PricingRule::updateOrCreate(['name'=>$name],[
                'target_type'=>$target,'customer_segment'=>$segment,'weekdays'=>$weekdays,'time_from'=>$from,'time_to'=>$to,
                'occupancy_min_pct'=>$occMin,'occupancy_max_pct'=>$occMax,'adjustment_type'=>$type,'adjustment_value'=>$value,
                'priority'=>$priority,'combinable'=>$combinable,'is_active'=>true,
            ]);
        }
    }
}
