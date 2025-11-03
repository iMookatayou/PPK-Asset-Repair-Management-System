<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AssetController,
    MaintenanceRequestController,
    AttachmentController,
    MaintenanceLogController
};

use App\Http\Controllers\Api\AssetController as ApiAssetController;

Route::middleware('auth:sanctum')->group(function () {

    // ---------- User info ----------
    Route::get('/user', fn (Request $r) => $r->user());

    // ---------- Assets ----------
    // ได้ครบ: index, store, show, update, destroy
    Route::apiResource('assets', AssetController::class)->only([
        'index', 'store', 'show', 'update', 'destroy',
    ]);

    // ---------- Maintenance Requests ----------
    Route::prefix('repair-requests')->name('repair-requests.')->group(function () {
        Route::get('/',                    [MaintenanceRequestController::class, 'index'])->name('index');
        Route::post('/',                   [MaintenanceRequestController::class, 'store'])->name('store');
        Route::get('/{req}',               [MaintenanceRequestController::class, 'show'])->name('show');
        Route::put('/{req}',               [MaintenanceRequestController::class, 'update'])->name('update');
        Route::post('/{req}/transition',   [MaintenanceRequestController::class, 'transition'])->name('transition');

        // Logs ของคำขอซ่อม
        Route::get('/{req}/logs',          [MaintenanceLogController::class, 'index'])->name('logs');
    });

    // ---------- Attachments ----------
    Route::post('/attachments',                 [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}',  [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});
