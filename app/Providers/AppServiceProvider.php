<?php

namespace App\Providers;

use App\Integrations\ZApi\ZApiWhatsappGateway;
use App\Services\WhatsappGateway;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsappGateway::class, ZApiWhatsappGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Str::endsWith(request()->getHost(), '.trycloudflare.com')) {
            // Tunnel requests cannot access the local Vite dev server, so fall back to built assets.
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }
    }
}
