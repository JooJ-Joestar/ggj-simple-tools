<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/otp/send-password', [AuthController::class, 'sendOtp'])->name('otp.send');
Route::get('/otp/login', [AuthController::class, 'loginWithToken'])->name('otp.login');
Route::get('/opt/anonymous-login', [AuthController::class, 'anonymousLogin'])->name('otp.anonymous');
