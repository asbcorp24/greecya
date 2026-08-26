<?php

use App\Http\Controllers\PointOfSaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin', 'audit.admin'])->group(function () {
    Route::get('/reception/sale', [PointOfSaleController::class, 'reception'])->name('reception.sales.create');
    Route::post('/reception/sale', [PointOfSaleController::class, 'storeReception'])->name('reception.sales.store');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/sales', [PointOfSaleController::class, 'admin'])->name('sales.index');
        Route::post('/sales', [PointOfSaleController::class, 'storeAdmin'])->name('sales.store');
    });
});
