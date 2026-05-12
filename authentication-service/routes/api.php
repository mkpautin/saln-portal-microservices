<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::post('/login/send-code', [AuthController::class, 'sendCode'])->name('login.send-code');
    Route::post('/login/verify', [AuthController::class, 'verifyCode'])->name('login.verify');
});

Route::middleware('auth:api')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
