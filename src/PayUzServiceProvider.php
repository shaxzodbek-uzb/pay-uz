<?php

namespace Goodoneuz\PayUz;

use Illuminate\Support\ServiceProvider;

class PayUzServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */

        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'pay-uz');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'pay-uz');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../publishable/Payments' => base_path('/app/Http/Controllers/Payments'),
                __DIR__.'/../config/config.php' => config_path('payuz.php')
            ], 'pay-uz-editable');
            $this->publishes([
                __DIR__.'/resources/assets' => public_path('vendor/pay-uz'),
            ], 'pay-uz-assets');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration.
        // NOTE: the key must be 'payuz' to match every config('payuz') read in the
        // package (routes, gateways) and the published file name config_path('payuz.php').
        // It was previously merged under 'pay-uz', so the package defaults (including the
        // control-panel auth middleware) silently never applied on a default install.
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'payuz');

        // Register the main class to use with the facade
        $this->app->singleton('pay-uz', function () {
            return new PayUz;
        });
    }
}
