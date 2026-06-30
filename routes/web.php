<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('chat.index')
        : view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
|
| Redirect all authenticated users to the chat page.
|
*/
Route::get('/dashboard', function () {
    return redirect()->route('chat.index');
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

Route::middleware(['auth', 'can:manage-users'])
    ->prefix('admin/manage-users')
    ->name('admin.users.')
    ->group(function () {

        Route::get('/', [AdminChatController::class, 'manageUsers'])
            ->name('index');
    });

Route::middleware(['auth', 'can:manage-chat'])
    ->prefix('conversations')
    ->name('conversations.')
    ->group(function () {

        Route::get('/', [ConversationController::class, 'index'])
            ->name('index');

        Route::put('/{conversation}', [ConversationController::class, 'update'])
            ->name('update');
    });

Route::middleware(['auth'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {

        Route::post('/toggle-chat', [SettingController::class, 'toggleChat'])
            ->name('toggle-chat');

        Route::get('/status', [SettingController::class, 'getStatus'])
            ->name('status');
    });

require __DIR__ . '/auth.php';