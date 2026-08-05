<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = Lead::query()->with('manager')->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->paginate(25)->withQueryString();

        return view('admin.leads.index', compact('leads'));
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'won', 'lost'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $lead->update($data + ['assigned_to' => $lead->assigned_to ?: $request->user()->id]);

        return back()->with('success', 'Лид обновлён.');
    }
}
