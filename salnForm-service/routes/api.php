<?php

use App\Http\Controllers\SalnFormController;
use App\Http\Controllers\SalnPdfController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::get('/saln', [SalnFormController::class, 'edit'])->name('saln.edit');
    Route::post('/saln/draft', [SalnFormController::class, 'draft'])->name('saln.draft');
    Route::get('/saln/export', [SalnFormController::class, 'export'])->name('saln.export');
    Route::post('/saln/import', [SalnFormController::class, 'import'])->name('saln.import');
    Route::post('/saln/pdf', [SalnPdfController::class, 'generate'])->name('saln.pdf');
    Route::get('/saln/pdf/download/{token}', [SalnPdfController::class, 'download'])->name('saln.pdf.download');
    Route::post('/saln', [SalnFormController::class, 'update'])->name('saln.update');
});