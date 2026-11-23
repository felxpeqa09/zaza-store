<?php

namespace App\Providers;

use App\Payments\MercadoPagoGateway;
use App\Payments\PaymentGateway;
use App\Storage\SupabaseStorage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MercadoPagoGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('supabase-s3', fn ($app, array $config) => SupabaseStorage::make($app['filesystem'], $config));
        Model::preventLazyLoading($this->app->isLocal());

        foreach (['storefront-login' => 25, 'storefront-registration' => 5, 'password-recovery' => 6, 'password-reset' => 8] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($attempts)->by($request->ip()));
        }
    }
}
