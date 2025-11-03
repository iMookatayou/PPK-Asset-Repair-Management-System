<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AssetController,
    MaintenanceRequestController,
    AttachmentController,
    MaintenanceLogController
};

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user());

    Route::name('api.')->group(function () {
      Route::apiResource('assets', AssetController::class);
  });
      
    Route::prefix('repair-requests')->name('repair-requests.')->group(function () {
        Route::get('/',                  [MaintenanceRequestController::class, 'index'])->name('index');
        Route::post('/',                 [MaintenanceRequestController::class, 'store'])->name('store');
        Route::get('/{req}',             [MaintenanceRequestController::class, 'show'])->name('show');
        Route::put('/{req}',             [MaintenanceRequestController::class, 'update'])->name('update');
        Route::post('/{req}/transition', [MaintenanceRequestController::class, 'transition'])->name('transition');
        Route::get('/{req}/logs',        [MaintenanceLogController::class, 'index'])->name('logs');
    });

    Route::post('/attachments',                [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});
