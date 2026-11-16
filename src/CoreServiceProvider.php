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
use KhattakBits\Core\Support\MigrationPaths;
use KhattakBits\Core\Support\ModuleRegistry;
use KhattakBits\Core\Support\SettingsStore;
use RuntimeException;

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

    /**
     * D2 split ke baad migrations do folders mein hain (docs/platform/03 D2.3).
     *
     * - `khattakbits.tenancy.enabled = false` (CP / starter kit / tests): dono folders
     *   `loadMigrationsFrom()` se register hoti hain, is liye plain `php artisan migrate`
     *   ek hi DB mein poora schema bana deta hai.
     * - `true` (multi-DB product): sirf `central/` register hota hai; `tenant/` folders
     *   `tenancy.migration_parameters['--path']` se `tenants:migrate` ko milte hain.
     */
    private function bootMigrations(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $tenancyEnabled = (bool) config('khattakbits.tenancy.enabled', false);

        $folders = MigrationPaths::folders($this->packagePath('database/migrations'), $registry);

        foreach (MigrationPaths::forMigrate($folders, $tenancyEnabled) as $path) {
            $this->loadMigrationsFrom($path);
        }

        if ($tenancyEnabled) {
            $this->exposeTenantMigrations($folders['tenant']);
        }
    }

    /**
     * Tenant folders ko `tenants:migrate` ke `--path` mein daalta hai.
     *
     * `config(['tenancy.migration_parameters.--path' => ...])` kaam nahi karta: `Arr::set()`
     * dot ko nesting samajhta hai, is liye poora array replace hota hai.
     *
     * @param  list<string>  $tenantPaths
     */
    private function exposeTenantMigrations(array $tenantPaths): void
    {
        // Flag faisla karta hai; yeh check sirf misconfiguration pakadne ke liye hai —
        // package ke baghair flag true ho to tenant migrations khamoshi se gayab ho jatin.
        // String check: `stancl/tenancy` optional dependency hai, is liye `use` import
        // nahi karte (class constant compile-time par resolve hota hai, magar static
        // analysers missing class par shor machate hain).
        if (! class_exists('Stancl\Tenancy\Tenancy')) {
            throw new RuntimeException(
                'khattakbits.tenancy.enabled is true but stancl/tenancy is not installed. '.
                'Tenant migrations would never run — install the package or set KB_TENANCY_ENABLED=false.'
            );
        }

        $params = config('tenancy.migration_parameters', []);

        $params['--path'] = array_values(array_unique(array_merge($tenantPaths, $params['--path'] ?? [])));

        config(['tenancy.migration_parameters' => $params]);
    }

    public function register(): void
    {
        // Config defaults.
        //
        // `mergeConfigFrom()` `config:cache` ke saath **kaam karta hai**: `config:cache`
        // pehle app boot karta hai (providers ke saath), aur us boot mein merge hui
        // defaults cache file (`bootstrap/cache/config.php`) mein chali jati hain. Yani
        // package ki config keys cache hone ke baad bhi mojood rehti hain — yeh P0a mein
        // verify kiya gaya (`config:show` cache ke baad bhi package keys dikhata hai).
        //
        // Do asli caveats:
        // 1. Published copy (`vendor:publish --tag=khattakbits-config`) ki keys package
        //    defaults ko override karti hain — publish kiya hua file hi source of truth hai.
        // 2. Cache banna ke baad package ki config file edit karne se kuch nahi hota jab
        //    tak `config:cache` dobara na chale — yani deploy par cache rebuild lazmi hai.
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
        $this->bootMigrations();

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
