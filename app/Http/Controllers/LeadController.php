<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'request' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['accepted'],
        ]);

        Lead::query()->create($data + ['channel' => 'site', 'status' => 'new']);

        return back()->with('lead_success', 'Спасибо! Администратор свяжется с вами и подберёт удобное время.');
    }
}
