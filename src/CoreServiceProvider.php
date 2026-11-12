<?php

namespace KhattakBits\Core;

use Illuminate\Support\ServiceProvider;

/**
 * Package entry point. Bindings, routes, config and commands are moved here step
 * by step (docs/platform/03-EXTRACTION-PLAN.md, step 7).
 *
 * Migrations land here earlier (step 3) kyunke models aur unki tables aik hi
 * unit hain: agar sirf models package mein hon aur tables host mein, to koi bhi
 * product jo `khattakbits/core` install karega uske paas users/settings/email
 * tables nahi hongi. Host ki apni product-specific migrations
 * (`database/migrations`) apni jagah chalti rehti hain — Laravel dono paths ko
 * filename se sort karta hai, is liye order `0001_...` se hi barqarar rehta hai.
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
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));
    }
}
