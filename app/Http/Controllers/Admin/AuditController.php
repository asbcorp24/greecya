<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email,role')
            ->when($request->filled('user_id'), fn($q)=>$q->where('user_id',$request->integer('user_id')))
            ->when($request->filled('action'), fn($q)=>$q->where('action',$request->string('action')))
            ->when($request->filled('route'), fn($q)=>$q->where('route_name','like','%'.$request->string('route').'%'))
            ->when($request->filled('date_from'), fn($q)=>$q->whereDate('created_at','>=',$request->date('date_from')))
            ->when($request->filled('date_to'), fn($q)=>$q->whereDate('created_at','<=',$request->date('date_to')))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs'=>$logs,
            'users'=>User::where('role','!=','customer')->orderBy('name')->get(['id','name']),
            'actions'=>AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
