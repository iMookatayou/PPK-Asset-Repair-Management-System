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

// Admin User Controller
use App\Http\Controllers\Admin\UserController as AdminUserController;

// หน้าแรก → login
Route::redirect('/', '/login');

// ---------------------
// Guest-only
// ---------------------
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// ---------------------
// Authenticated
// ---------------------
Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/repair/dashboard', [RepairDashboardController::class, 'index'])->name('repair.dashboard');
    Route::get('/dashboard', fn () => redirect()->route('repair.dashboard'))->name('dashboard');

    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/',       [MaintenanceRequestController::class, 'indexPage'])->name('index');
            Route::get('/create', [MaintenanceRequestController::class, 'createPage'])->name('create');
            Route::get('/{req}',  [MaintenanceRequestController::class, 'showPage'])->name('show');
            Route::post('/',      [MaintenanceRequestController::class, 'store'])->name('store');

            Route::post('/{req}/transition',  [MaintenanceRequestController::class, 'transitionFromBlade'])->name('transition');
            Route::post('/{req}/attachments', [MaintenanceRequestController::class, 'uploadAttachmentFromBlade'])->name('attachments');

            Route::get('/{req}/logs', [MaintenanceLogController::class, 'index'])->name('logs');
        });
    });

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/threads', [ChatController::class, 'storeThread'])->name('chat.store');
    Route::get('/chat/threads/{thread}', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/chat/threads/{thread}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/threads/{thread}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');

    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/',              [AssetController::class, 'indexPage'])->name('index');
        Route::get('/create',        [AssetController::class, 'createPage'])->name('create');
        Route::post('/',             [AssetController::class, 'storePage'])->name('store');
        Route::get('/{asset}',       [AssetController::class, 'showPage'])->name('show');
        Route::get('/{asset}/edit',  [AssetController::class, 'editPage'])->name('edit');
        Route::put('/{asset}',       [AssetController::class, 'updatePage'])->name('update');
        Route::delete('/{asset}',    [AssetController::class, 'destroyPage'])->name('destroy');
    });

    Route::prefix('admin')->name('admin.')->middleware('can:manage-users')->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',            [AdminUserController::class, 'index'])->name('index');
            Route::get('/create',      [AdminUserController::class, 'create'])->name('create');
            Route::post('/',           [AdminUserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{user}',      [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{user}',   [AdminUserController::class, 'destroy'])->name('destroy');

            Route::post('/bulk', [AdminUserController::class, 'bulk'])->name('bulk');
        });
    });

    Route::get('/repairs/my-jobs', [MaintenanceRequestController::class, 'myJobsPage'])->name('repairs.my_jobs');
    Route::get('/repairs/queue',   [MaintenanceRequestController::class, 'queuePage'])->name('repairs.queue');
});

require __DIR__ . '/auth.php';
