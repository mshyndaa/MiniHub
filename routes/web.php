<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/media/{user}/{kind}', [MediaController::class, 'show'])->name('media');
        Route::post('/profile/{kind}', [MediaController::class, 'update'])->whereIn('kind', ['avatar', 'banner'])->name('profile.media.update');
        Route::delete('/profile/{kind}', [MediaController::class, 'destroy'])->whereIn('kind', ['avatar', 'banner'])->name('profile.media.destroy');

        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset');
            Route::put('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.active');
        });

        Route::middleware('role:notes')->prefix('notes')->name('notes.')->group(function () {
            Route::get('/', [NoteController::class, 'index'])->name('index');
            Route::post('/', [NoteController::class, 'store'])->name('store');
            Route::put('/{note}', [NoteController::class, 'update'])->name('update');
            Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('role:todo')->prefix('todo')->name('todo.')->group(function () {
            Route::get('/', [TodoController::class, 'index'])->name('index');
            Route::post('/', [TodoController::class, 'store'])->name('store');
            Route::put('/{todo}', [TodoController::class, 'toggle'])->name('toggle');
            Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
        });
    });
});
