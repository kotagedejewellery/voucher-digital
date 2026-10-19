<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ExportController;

Route::get('/', [CheckoutController::class,'landing'])->name('landing');
Route::post('/checkout', [CheckoutController::class,'checkout'])->middleware('throttle:10,1')->name('checkout');
Route::get('/pesanan/{token}', [CheckoutController::class,'show'])->middleware('throttle:90,1')->name('payment.show');
Route::post('/pesanan/{token}/confirm', [CheckoutController::class,'confirm'])->middleware('throttle:10,1')->name('payment.confirm');
Route::get('/qris/{asset}', [CheckoutController::class,'qris'])->whereNumber('asset')->withoutMiddleware('web')->name('qris.show');
Route::get('/v/{token}', [VoucherController::class,'show'])->middleware('throttle:90,1')->name('voucher.show');

Route::middleware('guest')->group(function () {
    Route::view('/admin/login', 'auth.login')->name('login');
    Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});
Route::middleware(['auth', 'active'])->prefix('admin')->group(function () {
    Route::get('/',[AdminController::class,'dashboard'])->name('admin.dashboard');
    Route::view('/pesanan','admin.orders')->name('admin.orders');
    Route::get('/pesanan/{order:reference}',[AdminController::class,'order'])->name('admin.order');
    Route::get('/voucher',[AdminController::class,'voucher'])->name('admin.voucher');
    Route::view('/riwayat-voucher','admin.voucher-history')->name('admin.voucher-history');
    Route::get('/pengaturan',[AdminController::class,'settings'])->name('admin.settings');
    Route::post('/pengaturan/qris',[SettingsController::class,'qris'])->name('admin.qris');
    Route::post('/pengaturan/branch',[SettingsController::class,'branch'])->name('admin.branch');
    Route::post('/pengaturan/support',[SettingsController::class,'support'])->name('admin.support');
    Route::post('/pengaturan/policy',[SettingsController::class,'policy'])->name('admin.policy');
    Route::post('/pengaturan/users/{user}',[SettingsController::class,'user'])->name('admin.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/pesanan/{order:reference}/approve',[AdminOrderController::class,'approve'])->name('admin.approve');
    Route::post('/pesanan/{order:reference}/correct',[AdminOrderController::class,'correct'])->name('admin.correct');
    Route::post('/vouchers/{code}/claim',[ClaimController::class,'claim'])->name('admin.claim');
    Route::post('/vouchers/{code}/transfer',[ClaimController::class,'transfer'])->name('admin.transfer');
    Route::post('/deliveries/{delivery}/retry',[DeliveryController::class,'retry'])->name('admin.retry');
    Route::post('/vouchers/{voucher}/resend',[DeliveryController::class,'resend'])->middleware('throttle:6,1')->name('admin.resend');
    Route::post('/vouchers/{voucher}/manual-delivery',[DeliveryController::class,'markManual'])->name('admin.mark-manual-delivery');
    Route::post('/pesanan/{order:reference}/support',[DeliveryController::class,'support'])->name('admin.support-case');
    Route::get('/exports/{export}',[ExportController::class,'download'])->name('admin.export');
});
