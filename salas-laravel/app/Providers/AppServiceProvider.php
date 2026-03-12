<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $base = rtrim((string) config('app.base_path', ''), '/');
        if ($base !== '') {
            $root = rtrim(config('app.url', ''), '/');
            if ($root !== '' && !str_ends_with($root, $base)) {
                URL::forceRootUrl($root . '/' . $base);
            }
        }
    }
}
