<?php

use Illuminate\Support\Facades\Route;
use KhattakBits\Core\Http\Controllers\AttachmentController;
use KhattakBits\Core\Http\Controllers\AuthController;
use KhattakBits\Core\Http\Controllers\DocumentActivityController;
use KhattakBits\Core\Http\Controllers\PasswordResetController;
use KhattakBits\Core\Http\Controllers\ProfileController;
use KhattakBits\Core\Http\Middleware\EnsureUserActive;

/*
|--------------------------------------------------------------------------
| Core web routes
|--------------------------------------------------------------------------
|
| `web` group explicitly lagaya gaya hai: `loadRoutesFrom()` file ko sirf
| `require` karta hai, wo host ke `withRouting(web: ...)` wala middleware
| group khud se apply nahi karta. Iske baghair session/CSRF/Inertia
| middleware missing ho jate.
|
| Product ke apne routes host ke `routes/web.php` mein rehte hain — core inhein
| chhoota nahi, is liye product route names ya URIs core se collide nahi karte.
|
*/

Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');

        Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
            ->name('password.email')->middleware('throttle:password-reset');

        Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])
            ->name('password.update')->middleware('throttle:password-reset');
    });

    Route::middleware(['auth', EnsureUserActive::class])->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

        Route::get('/attachments', [AttachmentController::class, 'index']);
        Route::post('/attachments', [AttachmentController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/attachments/{attachment}', [AttachmentController::class, 'show']);
        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

        // Document activity: views counter + lifecycle timeline
        Route::post('/documents/{type}/{id}/views', [DocumentActivityController::class, 'store'])
            ->where(['type' => '[a-z0-9_]+', 'id' => '[0-9]+'])
            ->middleware('throttle:120,1');
        Route::get('/documents/{type}/{id}/activity', [DocumentActivityController::class, 'show'])
            ->where(['type' => '[a-z0-9_]+', 'id' => '[0-9]+']);
    });
});
