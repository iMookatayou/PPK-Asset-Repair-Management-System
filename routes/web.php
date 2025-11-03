<?php

use Illuminate\Support\Facades\Route;

// Auth / Profile
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\PasswordController;

// App Modules (Web)
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\Repair\DashboardController as RepairDashboardController;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\AssetController;

// หน้าแรกชี้ไปหน้า login สำหรับ guest
Route::redirect('/', '/login');

// ---------------------
// Guest-only
// ---------------------
Route::middleware('guest')->group(function () {
    // Register
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// ---------------------
// Authenticated
// ---------------------
Route::middleware(['auth'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // Repair Dashboard
    Route::get('/repair/dashboard', [RepairDashboardController::class, 'index'])->name('repair.dashboard');
    // Dashboard ของระบบเดิมให้รีไดเรกต์มาหน้านี้ (สลับเป็น assets.index ได้ตามต้องการ)
    Route::get('/dashboard', fn () => redirect()->route('repair.dashboard'))->name('dashboard');

    // ---------------------
    // Maintenance Requests (Blade)
    // ---------------------
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::prefix('requests')->name('requests.')->group(function () {
            // หน้าเว็บ (Blade)
            Route::get('/',       [MaintenanceRequestController::class, 'indexPage'])->name('index');
            Route::get('/create', [MaintenanceRequestController::class, 'createPage'])->name('create');
            Route::get('/{req}',  [MaintenanceRequestController::class, 'showPage'])->name('show');

            // submit จากฟอร์ม create
            Route::post('/', [MaintenanceRequestController::class, 'store'])->name('store');

            // เปลี่ยนสถานะ & อัปโหลดไฟล์จากหน้าเว็บ
            Route::post('/{req}/transition',  [MaintenanceRequestController::class, 'transitionFromBlade'])->name('transition');
            Route::post('/{req}/attachments', [MaintenanceRequestController::class, 'uploadAttachmentFromBlade'])->name('attachments');

            // (ถ้ามีหน้า logs)
            Route::get('/{req}/logs', [MaintenanceLogController::class, 'index'])->name('logs');
        });
    });

    // ---------------------
    // Chat (Blade + polling JSON แบบง่าย)
    // ---------------------
    Route::get('/chat',                           [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/threads',                  [ChatController::class, 'storeThread'])->name('chat.store');
    Route::get('/chat/threads/{thread}',          [ChatController::class, 'show'])->name('chat.show');
    Route::get('/chat/threads/{thread}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/threads/{thread}/messages',[ChatController::class, 'storeMessage'])->name('chat.messages.store');

    // ---------------------
    // Assets (Blade) — resource ครบ: index/create/store/show/edit/update/destroy
    // ---------------------
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/',              [AssetController::class, 'indexPage'])->name('index');
        Route::get('/create',        [AssetController::class, 'createPage'])->name('create');
        Route::post('/',             [AssetController::class, 'storePage'])->name('store');
        Route::get('/{asset}',       [AssetController::class, 'showPage'])->name('show');
        Route::get('/{asset}/edit',  [AssetController::class, 'editPage'])->name('edit');
        Route::put('/{asset}',       [AssetController::class, 'updatePage'])->name('update');
        Route::delete('/{asset}',    [AssetController::class, 'destroyPage'])->name('destroy');
    });
    // ---------------------
    // Users (placeholder สำหรับหน้า Users)
    // ---------------------
    Route::prefix('users')->name('users.')->group(function () {
        Route::view('/', 'users.index')->name('index'); // สร้าง resources/views/users/index.blade.php ไว้ก่อน
    });
});

// auth routes (login/logout/forgot...) จาก Breeze/Fortify/Jetstream ฯลฯ
require __DIR__ . '/auth.php';
