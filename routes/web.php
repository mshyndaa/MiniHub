<?php

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NoteAttachmentController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteFolderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SharedNoteController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::pattern('id', '[0-9]+');
Route::pattern('noteId', '[0-9]+');
Route::pattern('versionId', '[0-9]+');

// Read-only public link to a note its owner chose to share.
Route::get('/s/{token}', SharedNoteController::class)->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('notes.shared');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications', [NotificationController::class, 'store'])->middleware('role:admin')->name('notifications.store');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->middleware('role:admin')->name('notifications.destroy');
        Route::get('/media/{user}/{kind}', [MediaController::class, 'show'])->name('media');
        Route::post('/profile/{kind}', [MediaController::class, 'update'])->whereIn('kind', ['avatar', 'banner'])->name('profile.media.update');
        Route::delete('/profile/{kind}', [MediaController::class, 'destroy'])->whereIn('kind', ['avatar', 'banner'])->name('profile.media.destroy');

        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset');
            Route::put('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.active');

            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

            Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
            Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
            Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
            Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
        });

        Route::middleware('role:stock')->prefix('stock')->name('stock.')->group(function () {
            Route::get('/', [StockController::class, 'index'])->name('index');
            Route::get('/tv', [StockController::class, 'tv'])->name('tv');
            Route::get('/data', [StockController::class, 'data'])->name('data');
            Route::post('/{id}/adjust', [StockController::class, 'adjust'])->name('adjust');
            Route::post('/', [StockController::class, 'store'])->name('store');
            Route::put('/{id}', [StockController::class, 'update'])->name('update');
            Route::delete('/{id}', [StockController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('role:spin')->prefix('spin')->name('spin.')->group(function () {
            Route::get('/', [SpinController::class, 'index'])->name('index');
            Route::get('/tv', [SpinController::class, 'tv'])->name('tv');
            Route::get('/state', [SpinController::class, 'state'])->name('state');
            Route::post('/state', [SpinController::class, 'push'])->name('push');
        });

        Route::middleware('role:notes')->prefix('notes')->name('notes.')->group(function () {
            Route::get('/', [NoteController::class, 'index'])->name('index');
            Route::post('/', [NoteController::class, 'store'])->name('store');
            Route::delete('/trash', [NoteController::class, 'emptyTrash'])->name('trash.empty');

            Route::post('/folders', [NoteFolderController::class, 'store'])->name('folders.store');
            Route::put('/folders/{id}', [NoteFolderController::class, 'update'])->name('folders.update');
            Route::delete('/folders/{id}', [NoteFolderController::class, 'destroy'])->name('folders.destroy');

            Route::get('/{id}', [NoteController::class, 'edit'])->name('edit');
            Route::put('/{id}', [NoteController::class, 'update'])->name('update');
            Route::delete('/{id}', [NoteController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle/{field}', [NoteController::class, 'toggle'])->name('toggle');
            Route::post('/{id}/duplicate', [NoteController::class, 'duplicate'])->name('duplicate');
            Route::post('/{id}/restore', [NoteController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [NoteController::class, 'forceDelete'])->name('force');
            Route::get('/{id}/export/{format}', [NoteController::class, 'export'])->name('export');
            Route::post('/{id}/share', [NoteController::class, 'share'])->name('share');
            Route::get('/{id}/versions', [NoteController::class, 'versions'])->name('versions');
            Route::post('/{id}/versions/{versionId}/restore', [NoteController::class, 'restoreVersion'])->name('versions.restore');

            Route::post('/{noteId}/attachments', [NoteAttachmentController::class, 'store'])->name('attachments.store');
            Route::get('/{noteId}/attachments/{id}', [NoteAttachmentController::class, 'show'])->name('attachments.show');
            Route::delete('/{noteId}/attachments/{id}', [NoteAttachmentController::class, 'destroy'])->name('attachments.destroy');
        });
    });
});
