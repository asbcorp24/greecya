<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingIntegration;
use App\Models\AccountingSyncRun;
use App\Services\AccountingExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountingController extends Controller
{
    public function index()
    {
        return view('admin.accounting.index', [
            'integration' => AccountingIntegration::first(),
            'runs' => AccountingSyncRun::with('user')->latest('started_at')->limit(50)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|max:190',
            'driver'=>['required',Rule::in(['json_http','xml_http'])],
            'endpoint_url'=>'nullable|url|max:500',
            'username'=>'nullable|string|max:190',
            'password'=>'nullable|string|max:500',
            'token'=>'nullable|string|max:1000',
            'organization_code'=>'nullable|string|max:100',
            'format_version'=>'required|string|max:20',
            'timeout'=>'nullable|integer|min:5|max:120',
        ]);

        $integration = AccountingIntegration::first() ?: new AccountingIntegration();
        if (empty($data['password'])) unset($data['password']);
        if (empty($data['token'])) unset($data['token']);
        $data['options'] = ['timeout'=>(int)($data['timeout'] ?? 30)];
        unset($data['timeout']);
        $data['is_active'] = $request->boolean('is_active');
        $integration->fill($data)->save();

        return back()->with('success','Настройки обмена с 1С сохранены.');
    }

    public function export(Request $request, AccountingExportService $service)
    {
        [$from,$to,$format] = $this->period($request);
        $integration = AccountingIntegration::first();
        $payload = $service->build($from,$to,$integration);
        $body = $service->encode($payload,$format);

        AccountingSyncRun::create([
            'accounting_integration_id'=>$integration?->id,'user_id'=>$request->user()->id,'direction'=>'export','format'=>$format,'status'=>'completed',
            'period_from'=>$from,'period_to'=>$to,'record_counts'=>$payload['meta']['counts'],'checksum'=>hash('sha256',$body),'started_at'=>now(),'finished_at'=>now(),
        ]);

        $extension = $format === 'xml' ? 'xml' : 'json';
        $mime = $format === 'xml' ? 'application/xml; charset=UTF-8' : 'application/json; charset=UTF-8';
        return response($body,200,['Content-Type'=>$mime,'Content-Disposition'=>'attachment; filename="greecya-1c-'.$from->format('Ymd').'-'.$to->format('Ymd').'.'.$extension.'"']);
    }

    public function push(Request $request, AccountingExportService $service)
    {
        [$from,$to] = $this->period($request);
        $integration = AccountingIntegration::first();
        if (! $integration || ! $integration->is_active) return back()->withErrors(['accounting'=>'Интеграция с 1С не включена.']);

        $format = str_contains($integration->driver,'xml') ? 'xml' : 'json';
        $run = AccountingSyncRun::create([
            'accounting_integration_id'=>$integration->id,'user_id'=>$request->user()->id,'direction'=>'export','format'=>$format,'status'=>'running','period_from'=>$from,'period_to'=>$to,'started_at'=>now(),
        ]);

        try {
            $payload = $service->build($from,$to,$integration);
            $body = $service->encode($payload,$format);
            $response = $service->push($integration,$payload);
            $ok = $response->successful();
            $run->update([
                'status'=>$ok?'completed':'failed','record_counts'=>$payload['meta']['counts'],'http_status'=>$response->status(),'checksum'=>hash('sha256',$body),
                'error_text'=>$ok?null:mb_substr($response->body(),0,5000),'finished_at'=>now(),
            ]);
            if ($ok) {
                $integration->update(['last_synced_at'=>now()]);
                return back()->with('success','Данные переданы в 1С. HTTP '.$response->status().'.');
            }
            return back()->withErrors(['accounting'=>'1С вернула HTTP '.$response->status().': '.mb_substr($response->body(),0,500)]);
        } catch (\Throwable $e) {
            $run->update(['status'=>'failed','error_text'=>mb_substr($e->getMessage(),0,5000),'finished_at'=>now()]);
            return back()->withErrors(['accounting'=>'Ошибка обмена: '.$e->getMessage()]);
        }
    }

    private function period(Request $request): array
    {
        $data = $request->validate(['from'=>'required|date','to'=>'required|date|after_or_equal:from','format'=>['nullable',Rule::in(['json','xml'])]]);
        return [Carbon::parse($data['from'])->startOfDay(),Carbon::parse($data['to'])->endOfDay(),$data['format']??'json'];
    }
}
