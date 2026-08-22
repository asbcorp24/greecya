<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CorporateAccount;
use App\Models\CorporateMember;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerInteraction;
use App\Models\DocumentTemplate;
use App\Models\Lead;
use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CrmPlusController extends Controller
{
    public function index()
    {
        return view('admin.crm-plus.index', [
            'tasks' => CrmTask::with(['customer', 'lead', 'assignee'])->orderByRaw("CASE WHEN status='open' THEN 0 ELSE 1 END")->orderBy('due_at')->limit(100)->get(),
            'interactions' => CustomerInteraction::with(['customer', 'lead'])->latest('occurred_at')->limit(100)->get(),
            'campaigns' => Campaign::latest()->limit(50)->get(),
            'corporates' => CorporateAccount::with(['members.customer'])->orderBy('name')->get(),
            'templates' => DocumentTemplate::orderBy('name')->get(),
            'documents' => CustomerDocument::with('customer')->latest()->limit(50)->get(),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone', 'email', 'marketing_consent']),
            'leads' => Lead::latest()->limit(100)->get(),
            'users' => User::whereIn('role', ['admin', 'manager'])->orderBy('name')->get(),
        ]);
    }

    public function task(Request $request)
    {
        $data = $request->validate([
            'lead_id' => 'nullable|exists:leads,id',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_to' => 'nullable|exists:users,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:190',
            'description' => 'nullable|string|max:3000',
            'due_at' => 'nullable|date',
        ]);
        abort_if(empty($data['lead_id']) && empty($data['customer_id']), 422, 'Выберите клиента или лид.');
        CrmTask::create($data + ['status' => 'open']);
        return back()->with('success', 'CRM-задача создана.');
    }

    public function complete(CrmTask $task)
    {
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        return back()->with('success', 'Задача завершена.');
    }

    public function interaction(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'lead_id' => 'nullable|exists:leads,id',
            'channel' => 'required|string|max:50',
            'direction' => 'required|in:in,out',
            'subject' => 'nullable|string|max:190',
            'body' => 'nullable|string|max:5000',
            'occurred_at' => 'required|date',
        ]);
        abort_if(empty($data['lead_id']) && empty($data['customer_id']), 422, 'Выберите клиента или лид.');
        CustomerInteraction::create($data + ['user_id' => $request->user()->id]);
        return back()->with('success', 'Контакт с клиентом записан.');
    }

    public function campaign(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'channel' => ['required', Rule::in(['email', 'sms', 'telegram', 'whatsapp', 'push'])],
            'subject' => 'nullable|string|max:190',
            'body' => 'required|string|max:5000',
        ]);
        Campaign::create($data + ['status' => 'draft', 'audience' => ['marketing_consent' => true]]);
        return back()->with('success', 'Рассылка создана.');
    }

    public function launch(Campaign $campaign)
    {
        abort_unless(in_array($campaign->status, ['draft', 'queued'], true), 422, 'Кампания уже отправлена или недоступна для запуска.');
        $queued = 0;
        foreach (Customer::where('marketing_consent', true)->get() as $customer) {
            $recipient = $campaign->channel === 'email' ? $customer->email : $customer->phone;
            if (! $recipient) {
                continue;
            }
            MessageLog::updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'customer_id' => $customer->id,
                    'channel' => $campaign->channel,
                    'recipient' => $recipient,
                ],
                ['status' => 'queued', 'body' => $campaign->body]
            );
            $queued++;
        }
        $campaign->update(['status' => 'queued', 'scheduled_at' => $campaign->scheduled_at ?: now()]);
        return back()->with('success', 'Рассылка поставлена в очередь: '.$queued.' клиентов.');
    }

    public function corporate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'tax_id' => 'nullable|string|max:30',
            'contact_name' => 'nullable|string|max:190',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:190',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'credit_limit' => 'required|numeric|min:0',
        ]);
        CorporateAccount::create($data + ['status' => 'active']);
        return back()->with('success', 'Корпоративный клиент создан.');
    }

    public function corporateMember(Request $request, CorporateAccount $account)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'employee_number' => 'nullable|string|max:80',
        ]);
        CorporateMember::updateOrCreate(
            ['corporate_account_id' => $account->id, 'customer_id' => $data['customer_id']],
            ['employee_number' => $data['employee_number'] ?? null, 'status' => 'active']
        );
        return back()->with('success', 'Сотрудник добавлен в корпоративную группу.');
    }

    public function template(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'type' => 'required|string|max:80',
            'body' => 'required|string',
        ]);
        DocumentTemplate::create($data + ['is_active' => true]);
        return back()->with('success', 'Шаблон документа создан.');
    }

    public function document(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'document_template_id' => 'required|exists:document_templates,id',
            'type' => 'required|string|max:80',
        ]);
        $customer = Customer::findOrFail($data['customer_id']);
        $template = DocumentTemplate::findOrFail($data['document_template_id']);
        $content = strtr($template->body, [
            '{{name}}' => $customer->name,
            '{{phone}}' => $customer->phone,
            '{{email}}' => $customer->email ?: '',
            '{{date}}' => now()->format('d.m.Y'),
        ]);
        CustomerDocument::create([
            'customer_id' => $customer->id,
            'document_template_id' => $template->id,
            'type' => $data['type'],
            'number' => 'DOC-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'status' => 'draft',
            'content' => $content,
        ]);
        return back()->with('success', 'Документ сформирован.');
    }

    public function sign(CustomerDocument $document)
    {
        $document->update(['status' => 'signed', 'sign_method' => 'manual', 'signed_at' => now()]);
        return back()->with('success', 'Документ отмечен подписанным.');
    }
}
