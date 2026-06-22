<?php

namespace Goodoneuz\PayUz;

use Illuminate\Support\ServiceProvider;
use Goodoneuz\PayUz\Support\Http\CurlHttpClient;
use Goodoneuz\PayUz\Fiscalization\FiscalizationManager;
use Goodoneuz\PayUz\Subscribe\SubscribeManager;
use Goodoneuz\PayUz\Checkout\CheckoutManager;
use Goodoneuz\PayUz\Bnpl\BnplManager;
use Goodoneuz\PayUz\Einvoice\EinvoiceManager;
use Goodoneuz\PayUz\Payments\DefaultPaymentResolver;
use Goodoneuz\PayUz\Payments\Contracts\PaymentResolver;

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
            // The old 'pay-uz-editable' tag also published the executable hook
            // files under app/Http/Controllers/Payments; those are gone (replaced
            // by a PaymentResolver + events), so only the config is published now.
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('payuz.php'),
            ], 'pay-uz-config');
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

        // Bind the payment resolver that maps your domain model <-> payment key,
        // validates amounts and post-processes responses. Configure your own
        // implementation under `payuz.payments.resolver`; PaymentService resolves
        // this binding. Replaces the old editable Payments/*.php hook files.
        $this->app->singleton(PaymentResolver::class, function ($app) {
            $class = config('payuz')['payments']['resolver'] ?? DefaultPaymentResolver::class;

            return $app->make($class);
        });

        // Register the fiscalization manager behind the `Fiscalizer` facade. The
        // event dispatcher is injected only when one is bound, so the manager
        // still works (without events) outside a full framework boot.
        $this->app->singleton('pay-uz-fiscalizer', function ($app) {
            $config     = config('payuz')['fiscalization'] ?? [];
            $dispatcher = $app->bound('events') ? $app['events'] : null;

            return new FiscalizationManager($config, new CurlHttpClient(), $dispatcher);
        });

        // Card tokenization + recurring charges behind the `Subscribe` facade.
        $this->app->singleton('pay-uz-subscribe', function ($app) {
            $config     = config('payuz')['subscribe'] ?? [];
            $dispatcher = $app->bound('events') ? $app['events'] : null;

            return new SubscribeManager($config, new CurlHttpClient(), $dispatcher);
        });

        // Card-acquiring aggregators behind the `Checkout` facade.
        $this->app->singleton('pay-uz-checkout', function ($app) {
            $config     = config('payuz')['checkout'] ?? [];
            $dispatcher = $app->bound('events') ? $app['events'] : null;

            return new CheckoutManager($config, new CurlHttpClient(), $dispatcher);
        });

        // BNPL / installments behind the `Bnpl` facade.
        $this->app->singleton('pay-uz-bnpl', function ($app) {
            $config     = config('payuz')['bnpl'] ?? [];
            $dispatcher = $app->bound('events') ? $app['events'] : null;

            return new BnplManager($config, new CurlHttpClient(), $dispatcher);
        });

        // E-invoicing / e-documents behind the `Einvoice` facade.
        $this->app->singleton('pay-uz-einvoice', function ($app) {
            $config     = config('payuz')['einvoice'] ?? [];
            $dispatcher = $app->bound('events') ? $app['events'] : null;

            return new EinvoiceManager($config, new CurlHttpClient(), $dispatcher);
        });
    }
}
