<?php

use App\Http\Controllers\Noc\NocController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;


Route::post('/submit-otp', [NocController::class, 'sendOtp'])->name('submit.otp');
Route::post('/verify-token', [NocController::class, 'verifyToken'])->name('verify.token');
Route::get('/verify-token', [NocController::class, 'showVerifyToken'])->name('verify.token.get');
Route::get('/get-set-password', [NocController::class, 'showSetPassword'])->name('set.password.get');
Route::post('/set-password', [NocController::class, 'setPassword'])->name('set.password');
Route::get('/resend-code', [NocController::class, 'reSendOtp'])->name('re.send.otp');
Route::match(['get', 'post'], '/{slug}', [HomeController::class, 'slug'])->where('slug', '.*');
