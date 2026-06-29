<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('chat.index')
        : view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('chat')->name('chat.')->group(function () {

    Route::get('/', [ChatController::class, 'index'])
        ->name('index');

    Route::middleware('chat.not_denied')->group(function () {

        Route::get('/{user}', [ChatController::class, 'show'])
            ->name('show');

        Route::get('/{user}/history', [ChatController::class, 'history'])
            ->name('history');

        Route::post('/send', [ChatController::class, 'send'])
            ->name('send');

        Route::post('/{user}/mark-read', [ChatController::class, 'markRead'])
            ->name('mark-read');
    });
});

Route::middleware(['auth', 'can:manage-chat'])
    ->prefix('admin/chat')
    ->name('admin.chat.')
    ->group(function () {

        Route::get('/', [AdminChatController::class, 'index'])
            ->name('index');

        Route::post('/{user}/deny', [AdminChatController::class, 'deny'])
            ->name('deny');

        Route::post('/{user}/restore', [AdminChatController::class, 'restore'])
            ->name('restore');

        Route::get('/{userA}/conversation/{userB}', [AdminChatController::class, 'conversation'])
            ->name('conversation');

        Route::delete('/message/{message}', [AdminChatController::class, 'deleteMessage'])
            ->name('delete-message');

        Route::get('/{user}/deny-log', [AdminChatController::class, 'denyLog'])
            ->name('deny-log');
    });

require __DIR__ . '/auth.php';
