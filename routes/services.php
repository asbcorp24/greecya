<?php

use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/services', [ServiceCatalogController::class, 'index'])->name('services.index');
Route::get('/services/{service:slug}', [ServiceCatalogController::class, 'show'])->name('services.show');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'audit.admin'])->group(function () {
    Route::get('/services', [AdminServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [AdminServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [AdminServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}/edit', [AdminServiceController::class, 'edit'])->name('services.edit');
    Route::patch('/services/{service}', [AdminServiceController::class, 'update'])->name('services.update');
    Route::patch('/services/{service}/toggle', [AdminServiceController::class, 'toggle'])->name('services.toggle');
    Route::post('/services/{service}/photos', [AdminServiceController::class, 'storePhotos'])->name('services.photos.store');
    Route::patch('/services/{service}/photos/{photo}', [AdminServiceController::class, 'updatePhoto'])->name('services.photos.update');
    Route::delete('/services/{service}/photos/{photo}', [AdminServiceController::class, 'destroyPhoto'])->name('services.photos.destroy');
});
