<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\DirectorDashboardController;
use App\Http\Controllers\Admin\PricingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','admin','audit.admin'])->group(function(){
    Route::get('/director', DirectorDashboardController::class)->name('director.dashboard');

    Route::get('/accounting', [AccountingController::class,'index'])->name('accounting.index');
    Route::put('/accounting', [AccountingController::class,'update'])->name('accounting.update');
    Route::post('/accounting/export', [AccountingController::class,'export'])->name('accounting.export');
    Route::post('/accounting/push', [AccountingController::class,'push'])->name('accounting.push');

    Route::get('/pricing', [PricingController::class,'index'])->name('pricing.index');
    Route::post('/pricing', [PricingController::class,'store'])->name('pricing.store');
    Route::patch('/pricing/{pricingRule}', [PricingController::class,'update'])->name('pricing.update');
    Route::delete('/pricing/{pricingRule}', [PricingController::class,'destroy'])->name('pricing.destroy');
});
