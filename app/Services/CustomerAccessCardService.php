<?php

namespace App\Services;

use App\Models\AccessCard;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerAccessCardService
{
    public function active(Customer $customer): ?AccessCard
    {
        return $customer->accessCards()
            ->where('type', 'qr')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest('issued_at')
            ->latest('id')
            ->first();
    }

    public function ensure(Customer $customer): AccessCard
    {
        return $this->active($customer) ?: $this->issue($customer);
    }

    public function reissue(Customer $customer): AccessCard
    {
        return DB::transaction(function () use ($customer) {
            $customer->accessCards()
                ->where('type', 'qr')
                ->where('status', 'active')
                ->lockForUpdate()
                ->get()
                ->each(fn (AccessCard $card) => $card->update(['status' => 'replaced']));

            return $this->issue($customer);
        });
    }

    private function issue(Customer $customer): AccessCard
    {
        do {
            $code = 'QR-'.Str::upper(Str::random(16));
        } while (AccessCard::query()->where('code', $code)->exists());

        return AccessCard::query()->create([
            'customer_id' => $customer->id,
            'code' => $code,
            'type' => 'qr',
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => null,
        ]);
    }
}
