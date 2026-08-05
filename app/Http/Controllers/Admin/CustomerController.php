<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->withCount(['bookings', 'orders'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(fn ($sub) => $sub->where('name', 'like', $q)->orWhere('phone', 'like', $q)->orWhere('email', 'like', $q));
            })
            ->latest()->paginate(30)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }
}
