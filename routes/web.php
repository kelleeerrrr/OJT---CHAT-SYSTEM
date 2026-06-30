<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('chat.index')
        : view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| Dashboard — redirect to chat
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return redirect()->route('chat.index');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Chat
|--------------------------------------------------------------------------
|
| /chat                       — conversation list           (chat.index)
| /chat/inbox                 — live JSON poll endpoint     (chat.inbox)
| /chat/{user}                — open thread                 (chat.show)
| /chat/{user}/history        — paginated history           (chat.history)
| /chat/send                  — send a message              (chat.send)
| /chat/{user}/mark-read      — mark thread read            (chat.mark-read)
| /chat/message/{message}     — delete own message          (chat.message.destroy)
|
*/
Route::middleware(['auth'])->prefix('chat')->name('chat.')->group(function () {

    // Always accessible (even when chat is denied — user can still see the list)
    Route::get('/',       [ChatController::class, 'index'])->name('index');

    // Polled every 5 s from the index page to update badges + previews live
    // Must be outside chat.not_denied so denied users still see their inbox state
    Route::get('/inbox',  [ChatController::class, 'inbox'])->name('inbox');

    // Blocked for denied users
    Route::middleware('chat.not_denied')->group(function () {
        Route::get('/{user}',               [ChatController::class, 'show'])->name('show');
        Route::get('/{user}/history',       [ChatController::class, 'history'])->name('history');
        Route::post('/send',                [ChatController::class, 'send'])->name('send');
        Route::post('/{user}/mark-read',    [ChatController::class, 'markRead'])->name('mark-read');

        // Soft-delete own message (not admin delete)
        Route::delete('/message/{message}', [ChatController::class, 'destroy'])->name('message.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Admin — Chat management
|--------------------------------------------------------------------------
|
| /admin/chat                              — user list + global toggle  (admin.chat.index)
| /admin/chat/{user}/deny                  — deny chat access           (admin.chat.deny)
| /admin/chat/{user}/restore               — restore chat access        (admin.chat.restore)
| /admin/chat/{userA}/conversation/{userB} — view thread between two    (admin.chat.conversation)
| /admin/chat/message/{message}            — hard-delete a message      (admin.chat.delete-message)
| /admin/chat/{user}/deny-log              — deny/restore history       (admin.chat.deny-log)
|
*/
Route::middleware(['auth', 'can:manage-chat'])
    ->prefix('admin/chat')
    ->name('admin.chat.')
    ->group(function () {

        Route::get('/', [AdminChatController::class, 'index'])->name('index');

        Route::post('/{user}/deny',    [AdminChatController::class, 'deny'])->name('deny');
        Route::post('/{user}/restore', [AdminChatController::class, 'restore'])->name('restore');

        Route::get('/{userA}/conversation/{userB}',
            [AdminChatController::class, 'conversation'])->name('conversation');

        Route::delete('/message/{message}',
            [AdminChatController::class, 'deleteMessage'])->name('delete-message');

        Route::get('/{user}/deny-log',
            [AdminChatController::class, 'denyLog'])->name('deny-log');
    });

/*
|--------------------------------------------------------------------------
| Admin — Conversations
|--------------------------------------------------------------------------
|
| /conversations          — list all conversations  (conversations.index)
| /conversations/{id}     — update a conversation   (conversations.update)
| /conversations/{id}     — delete a conversation   (conversations.destroy)
|
*/
Route::middleware(['auth', 'can:manage-chat'])
    ->prefix('conversations')
    ->name('conversations.')
    ->group(function () {

        Route::get('/',                  [ConversationController::class, 'index'])->name('index');
        Route::put('/{conversation}',    [ConversationController::class, 'update'])->name('update');
        Route::delete('/{conversation}', [ConversationController::class, 'destroy'])->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
|
| POST /settings/toggle-chat  — enable / disable chat globally  (settings.toggle-chat)
| GET  /settings/status       — return current chat_enabled     (settings.status)
|
*/
Route::middleware(['auth'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {

        // Superadmin only — gate this in SettingController or add 'can:toggle-chat'
        Route::post('/toggle-chat', [SettingController::class, 'toggleChat'])->name('toggle-chat');

        // Readable by any authenticated user (admin blade JS polls this)
        Route::get('/status', [SettingController::class, 'getStatus'])->name('status');
    });

/*
|--------------------------------------------------------------------------
| Auth (Breeze / Fortify generated)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';