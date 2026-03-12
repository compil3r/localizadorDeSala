<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Base absoluta para assets (scheme + host + base_path) para funcionar em subpasta
        $assetBase = request()
            ? rtrim(request()->getSchemeAndHttpHost() . rtrim((string) config('app.base_path', ''), '/'), '/')
            : rtrim((string) config('app.url', ''), '/');
        View::share('assetBase', $assetBase);
    }
}
