<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountAuthController extends Controller
{
    public function create() { return view('account.register'); }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'privacy' => ['accepted'],
        ]);
        $user = DB::transaction(function () use ($data) {
            $customer = Customer::query()->where('email', $data['email'])->orWhere('phone', $data['phone'])->first();
            if ($customer) $customer->update(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']]);
            else $customer = Customer::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'source' => 'account']);
            return User::create(['customer_id' => $customer->id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'role' => 'customer', 'password' => Hash::make($data['password'])]);
        });
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('account.dashboard')->with('success', 'Личный кабинет создан.');
    }
}
