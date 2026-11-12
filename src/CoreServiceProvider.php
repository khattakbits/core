<?php

namespace KhattakBits\Core;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use KhattakBits\Core\Console\CreateAdmin;
use KhattakBits\Core\Console\PruneEmails;
use KhattakBits\Core\Console\PurgeBin;
use KhattakBits\Core\Support\DocumentRegistry;
use KhattakBits\Core\Support\EmailTemplates;
use KhattakBits\Core\Support\ModuleRegistry;
use KhattakBits\Core\Support\SettingsStore;

/**
 * Package entry point.
 *
 * Yeh class host ke purane `App\Core\Providers\CoreServiceProvider` ka replacement
 * hai — wohi bindings, wohi boot steps, sirf namespace badla hua. Host ise
 * `composer.json` ke `extra.laravel.providers` se auto-discover karta hai, is liye
 * `bootstrap/providers.php` mein iska zikr nahi hona chahiye.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Package root (src/ ka parent).
     */
    private function packagePath(string $path = ''): string
    {
        return dirname(__DIR__).($path === '' ? '' : DIRECTORY_SEPARATOR.$path);
    }

    public function register(): void
    {
        // Config defaults. Product `vendor:publish --tag=khattakbits-config` se
        // apni copies rakhta hai; mergeConfigFrom sirf un installs ke liye hai
        // jinhone publish nahi kiya (config cache hone par yeh no-op hai —
        // is liye published copy hi asal source of truth hai).
        foreach (['khattakbits', 'attachments', 'documents', 'email', 'security'] as $name) {
            $this->mergeConfigFrom($this->packagePath("config/{$name}.php"), $name);
        }

        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(SettingsStore::class);
        $this->app->singleton(DocumentRegistry::class);
        $this->app->singleton(EmailTemplates::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));

        foreach ($this->app->make(ModuleRegistry::class)->all() as $module) {
            $migrations = $module['path'].'/Database/Migrations';
            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }
        }

        $this->loadRoutesFrom($this->packagePath('routes/web.php'));

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdmin::class, PurgeBin::class, PruneEmails::class]);

            // Product apni config copies publish karta hai. Published copy hi asal
            // source of truth hai (config cache ke saath mergeConfigFrom kaam nahi
            // karta), is liye yeh tag documentation ka hissa hai, optional nahi.
            $this->publishes([
                $this->packagePath('config/khattakbits.php') => config_path('khattakbits.php'),
                $this->packagePath('config/attachments.php') => config_path('attachments.php'),
                $this->packagePath('config/documents.php') => config_path('documents.php'),
                $this->packagePath('config/email.php') => config_path('email.php'),
                $this->packagePath('config/security.php') => config_path('security.php'),
            ], 'khattakbits-config');
        }

        // N+1: development/tests mein error, production mein sirf log (user ko 500 nahi)
        Model::preventLazyLoading();
        if ($this->app->environment('production')) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                logger()->warning("Lazy loading [{$relation}] on [".$model::class.'].');
            });
        }

        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);

        // Do limiter: (email + IP) aur sirf IP. Email badal kar bypass nahi ho sakta
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by('ip|'.$r->ip()),
        ]);

        // Password reset: IP par 5/min aur usi email par 3/min (dono lagte hain)
        RateLimiter::for('password-reset', fn (Request $r) => [
            Limit::perMinute(5)->by('ip|'.$r->ip()),
            Limit::perMinute(3)->by('email|'.mb_strtolower(trim((string) $r->input('email')))),
        ]);
    }
}
