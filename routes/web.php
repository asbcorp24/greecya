<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/offer', 'legal.offer')->name('offer');
Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
Route::get('/booking/slots', [BookingController::class, 'slots'])->name('booking.slots')->middleware('throttle:60,1');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store')->middleware('throttle:15,1');
Route::get('/booking/success/{booking}', [BookingController::class, 'success'])->name('booking.success');
Route::post('/request-call', [LeadController::class, 'store'])->name('lead.store')->middleware('throttle:10,1');
Route::get('/tickets', CatalogController::class)->name('catalog.index');
Route::post('/orders', [OrderController::class, 'store'])->name('order.store')->middleware('throttle:10,1');
Route::get('/orders/success/{order}', [OrderController::class, 'success'])->name('order.success');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::patch('/bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::get('/schedule', [AdminScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [AdminScheduleController::class, 'store'])->name('schedule.store');
    Route::delete('/schedule/{slot}', [AdminScheduleController::class, 'destroy'])->name('schedule.destroy');
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::patch('/leads/{lead}', [AdminLeadController::class, 'update'])->name('leads.update');
});
