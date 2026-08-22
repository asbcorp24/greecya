<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->middleware(['auth','customer'])->group(function(){
    Route::post('/memberships/{membership}/freeze',[AccountController::class,'freezeMembership'])->name('memberships.freeze');
    Route::post('/bookings/{booking}/cancel',[AccountController::class,'cancelBooking'])->name('bookings.cancel');
});
