<?php

namespace KhattakBits\Core\Routing;

use Illuminate\Support\Facades\Route;
use KhattakBits\Core\Http\Controllers\AttachmentController;
use KhattakBits\Core\Http\Controllers\AuthController;
use KhattakBits\Core\Http\Controllers\DocumentActivityController;
use KhattakBits\Core\Http\Controllers\PasswordResetController;
use KhattakBits\Core\Http\Controllers\ProfileController;
use KhattakBits\Core\Http\Middleware\EnsureUserActive;

/**
 * Core web routes — do groups, `docs/platform/07` §3.4.
 *
 * **Central** (login, password reset, logout, profile): identity central DB mein
 * hai, is liye yeh routes kabhi tenant prefix ke andar nahi jate.
 *
 * **Tenant-scoped** (attachments, document activity): file aur `document_views`
 * tenant ke hain. Multi-tenant mode mein yeh `/{tenant}/...` ke andar jate hain,
 * warna `Storage::download()` central disk par dekhega aur 404 dega.
 *
 * Faisla **flag** karta hai (`khattakbits.tenancy.enabled`), package ki maujoodgi
 * nahi — RK21 ka wohi sabak jo `03` D2.3 mein hai:
 *
 * | flag | tenant routes | Nateeja |
 * |---|---|---|
 * | `false` (CP, starter kit, tests) | bina prefix | aaj ke URLs bilkul wohi rehte hain |
 * | `true` (multi-DB product) | `/{tenant}/...` | `{tenant}` **pehla** param (07 §1.2) |
 *
 * `{tenant}` pehla param hona lazmi hai: `InitializeTenancyByPath` `parameterNames()[0]`
 * padhta hai. `/attachments/{tenant}/{attachment}` chupchaap non-tenant route ban jata.
 *
 * **Yeh file tenancy initialize nahi karti.** `InitializeTenancyByPath` middleware
 * product `routes/web.php` mein `{tenant}` group par lagta hai (P5) — core ka kaam
 * sirf URIs sahi jagah rakhna hai.
 */
final class CoreRoutes
{
    public static function register(bool $tenancyEnabled): void
    {
        // `web` group explicitly: `loadRoutesFrom()` file ko sirf require karta hai,
        // host ke `withRouting(web: ...)` wala middleware group khud apply nahi hota.
        Route::middleware('web')->group(function () use ($tenancyEnabled): void {
            self::guest();

            Route::middleware(['auth', EnsureUserActive::class])->group(function () use ($tenancyEnabled): void {
                self::centralAuthenticated();

                if ($tenancyEnabled) {
                    Route::prefix('{tenant}')->group(static fn () => self::tenantScoped());
                } else {
                    self::tenantScoped();
                }
            });
        });
    }

    /** Login aur password reset — break-glass entry, hamesha central (07 §3.4). */
    private static function guest(): void
    {
        Route::middleware('guest')->group(function (): void {
            Route::get('/login', [AuthController::class, 'create'])->name('login');
            Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');

            Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
            Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
                ->name('password.email')->middleware('throttle:password-reset');

            Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
            Route::post('/reset-password', [PasswordResetController::class, 'reset'])
                ->name('password.update')->middleware('throttle:password-reset');
        });
    }

    /** Logout aur profile — identity central hai, tenant prefix nahi (07 §3.4). */
    private static function centralAuthenticated(): void
    {
        Route::post('/logout', [AuthController::class, 'destroy']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    }

    /**
     * Tenant data wale routes. Tenancy off par bina prefix (aaj ke URLs),
     * on par `/{tenant}` ke andar — dono soorton mein wohi code.
     */
    private static function tenantScoped(): void
    {
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
    }
}
