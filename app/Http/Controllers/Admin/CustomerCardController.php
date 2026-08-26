<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerAccessCardService;
use Illuminate\Http\Request;

class CustomerCardController extends Controller
{
    public function issue(Request $request, Customer $customer, CustomerAccessCardService $cards)
    {
        $this->authorizeManage($request);
        $card = $cards->ensure($customer);

        return back()->with('success', 'QR-карта клиента готова: '.$card->code);
    }

    public function reissue(Request $request, Customer $customer, CustomerAccessCardService $cards)
    {
        $this->authorizeManage($request);
        $card = $cards->reissue($customer);

        return back()->with('success', 'QR-карта перевыпущена. Новый код: '.$card->code);
    }

    public function print(Request $request, Customer $customer, CustomerAccessCardService $cards)
    {
        $this->authorizeView($request);
        $card = $cards->active($customer);
        abort_unless($card, 404, 'У клиента нет активной QR-карты. Сначала выдайте карту.');

        $customer->load(['memberships.plan']);
        $membership = $customer->memberships
            ->where('status', 'active')
            ->sortByDesc('ends_on')
            ->first();

        return view('admin.customers.card-print', compact('customer', 'card', 'membership'));
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->hasPermission('access.manage') || $user->hasPermission('sales.pos')),
            403
        );
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && (
                $user->hasPermission('customers.view') ||
                $user->hasPermission('access.view') ||
                $user->hasPermission('sales.pos')
            ),
            403
        );
    }
}
