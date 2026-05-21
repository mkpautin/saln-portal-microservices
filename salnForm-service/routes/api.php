<?php

use App\Http\Controllers\SalnFormController;
use App\Http\Controllers\SalnPdfController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::get('/data', [SalnFormController::class, 'edit'])->name('saln.edit');
    Route::get('/export', [SalnFormController::class, 'export'])->name('saln.export');
    Route::post('/import', [SalnFormController::class, 'import'])->name('saln.import');
    Route::post('/pdf', [SalnPdfController::class, 'generate'])->name('saln.pdf');
    Route::post('/update', [SalnFormController::class, 'update'])->name('saln.update');
});