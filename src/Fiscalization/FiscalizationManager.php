<?php

namespace Goodoneuz\PayUz\Fiscalization;

use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\CurlHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Fiscalization\Contracts\FiscalDriver;
use Goodoneuz\PayUz\Fiscalization\Drivers\OfdDriver;
use Goodoneuz\PayUz\Fiscalization\Drivers\NullDriver;
use Goodoneuz\PayUz\Fiscalization\Events\ReceiptFiscalized;
use Goodoneuz\PayUz\Fiscalization\Events\FiscalizationFailed;
use Goodoneuz\PayUz\Fiscalization\Exceptions\FiscalizationException;
use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * Resolves and drives fiscalization drivers — the entry point behind the
 * `Fiscalizer` facade. Mirrors the package's existing driver-selection style
 * (cf. {@see \Goodoneuz\PayUz\PayUz::driver()}) but as a configurable manager so
 * new OFD providers can be registered without editing the package:
 *
 *   Fiscalizer::extend('my-ofd', function (array $cfg, $http) { ... });
 *   $result = Fiscalizer::fiscalize($receipt);            // default driver
 *   $result = Fiscalizer::driver('ofd')->fiscalize($receipt);
 */
class FiscalizationManager
{
    /** @var array the `fiscalization` config block */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var \Illuminate\Contracts\Events\Dispatcher|null */
    protected $dispatcher;

    /** @var FiscalDriver[] resolved-driver cache, keyed by name */
    protected $drivers = [];

    /** @var callable[] custom driver factories registered via extend() */
    protected $customCreators = [];

    /**
     * @param array            $config     the `fiscalization` config block
     * @param HttpClient|null  $http       transport injected into HTTP drivers
     * @param mixed            $dispatcher an event dispatcher with dispatch(), or null
     */
    public function __construct(array $config = [], ?HttpClient $http = null, $dispatcher = null)
    {
        $this->config     = $config;
        $this->http       = $http ?: new CurlHttpClient();
        $this->dispatcher = $dispatcher;
    }

    /**
     * Resolve a driver by name (defaults to `fiscalization.default`).
     *
     * @param string|null $name
     * @return FiscalDriver
     * @throws FiscalizationException for an unknown driver
     */
    public function driver($name = null)
    {
        $name = $name ?: $this->defaultDriver();

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Register a custom driver factory.
     *
     * @param string   $name
     * @param callable $factory function(array $driverConfig, HttpClient $http): FiscalDriver
     * @return self
     */
    public function extend($name, callable $factory)
    {
        $this->customCreators[$name] = $factory;
        unset($this->drivers[$name]); // force re-resolution

        return $this;
    }

    /**
     * Fiscalize through a driver and emit the matching event. Transport/config
     * faults are converted to an unsuccessful FiscalResult so the caller has a
     * single, exception-free path for runtime failures. A structurally invalid
     * receipt is a programmer error, not a fiscalization fault, so
     * InvalidReceiptException is allowed to propagate — matching the direct
     * `driver()->fiscalize()` path.
     *
     * @param Receipt     $receipt
     * @param string|null $driver  driver name, or null for the default
     * @return FiscalResult
     * @throws InvalidReceiptException when the receipt is structurally invalid
     */
    public function fiscalize(Receipt $receipt, $driver = null)
    {
        $driverInstance = $this->driver($driver);

        try {
            $result = $driverInstance->fiscalize($receipt);
        } catch (InvalidReceiptException $e) {
            throw $e; // surface client/programmer errors loudly
        } catch (FiscalizationException $e) {
            $result = FiscalResult::failure($e->getMessage()); // driver/config fault
        } catch (TransportException $e) {
            $result = FiscalResult::failure($e->getMessage()); // network fault
        }

        if ($result->isSuccessful()) {
            $this->dispatch(new ReceiptFiscalized($receipt, $result, $driverInstance->name()));
        } else {
            $this->dispatch(new FiscalizationFailed($receipt, $result, $driverInstance->name()));
        }

        return $result;
    }

    /**
     * @return string
     */
    public function defaultDriver()
    {
        return isset($this->config['default']) && $this->config['default']
            ? $this->config['default']
            : 'null';
    }

    /**
     * @param string $name
     * @return FiscalDriver
     * @throws FiscalizationException
     */
    protected function resolve($name)
    {
        $driverConfig = $this->driverConfig($name);

        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name], $driverConfig, $this->http);
        }

        switch ($name) {
            case 'ofd':
                return new OfdDriver($driverConfig, $this->http);
            case 'null':
                return new NullDriver($driverConfig);
        }

        throw new FiscalizationException(sprintf('Fiscalization driver "%s" is not supported.', $name));
    }

    /**
     * @param string $name
     * @return array
     */
    protected function driverConfig($name)
    {
        return isset($this->config['drivers'][$name]) && is_array($this->config['drivers'][$name])
            ? $this->config['drivers'][$name]
            : [];
    }

    /**
     * @param object $event
     */
    protected function dispatch($event)
    {
        if ($this->dispatcher !== null) {
            $this->dispatcher->dispatch($event);
        }
    }
}
