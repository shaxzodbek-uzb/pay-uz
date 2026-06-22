<?php

namespace Goodoneuz\PayUz\Bnpl;

use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\CurlHttpClient;
use Goodoneuz\PayUz\Bnpl\Contracts\BnplDriver;
use Goodoneuz\PayUz\Bnpl\Drivers\NullBnplDriver;
use Goodoneuz\PayUz\Bnpl\Events\ContractCreated;
use Goodoneuz\PayUz\Bnpl\Drivers\UzumNasiyaDriver;
use Goodoneuz\PayUz\Bnpl\Events\ContractConfirmed;
use Goodoneuz\PayUz\Bnpl\Events\ContractCancelled;
use Goodoneuz\PayUz\Bnpl\Exceptions\BnplException;

/**
 * Resolves and drives BNPL / installments drivers — the entry point behind the
 * `Bnpl` facade. Mirrors the other managers (Checkout/Subscribe/Fiscalization):
 * a configurable driver registry with extend(), plus high-level helpers that
 * emit events on the lifecycle transitions.
 *
 *   $elig = Bnpl::checkEligibility($phone);
 *   $plans = Bnpl::calculate($elig->buyerId(), $items);
 *   $contract = Bnpl::createContract($elig->buyerId(), $plans[0]->tariffId(), $items); // ContractCreated
 *   // redirect the buyer to $contract->webviewPath() to sign, then:
 *   Bnpl::confirm($contract->contractId());                                            // ContractConfirmed
 */
class BnplManager
{
    /** @var array the `bnpl` config block */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var \Illuminate\Contracts\Events\Dispatcher|null */
    protected $dispatcher;

    /** @var BnplDriver[] */
    protected $drivers = [];

    /** @var callable[] */
    protected $customCreators = [];

    /**
     * @param array           $config
     * @param HttpClient|null $http
     * @param mixed           $dispatcher
     */
    public function __construct(array $config = [], ?HttpClient $http = null, $dispatcher = null)
    {
        $this->config     = $config;
        $this->http       = $http ?: new CurlHttpClient();
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string|null $name
     * @return BnplDriver
     * @throws BnplException for an unknown driver
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
     * @param string   $name
     * @param callable $factory function(array $driverConfig, HttpClient $http): BnplDriver
     * @return self
     */
    public function extend($name, callable $factory)
    {
        $this->customCreators[$name] = $factory;
        unset($this->drivers[$name]);

        return $this;
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

    // --- helpers (default driver; use driver(name) for a specific one) ---

    /**
     * @param string $phone
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility
     */
    public function checkEligibility($phone)
    {
        return $this->driver()->checkEligibility($phone);
    }

    /**
     * @param int   $buyerId
     * @param array $items
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\InstallmentPlan[]
     */
    public function calculate($buyerId, array $items)
    {
        return $this->driver()->calculate($buyerId, $items);
    }

    /**
     * Create a contract and emit ContractCreated.
     *
     * @param int         $buyerId
     * @param string      $period
     * @param array       $items
     * @param string|null $extOrderId
     * @param string|null $returnUrl
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\Contract
     */
    public function createContract($buyerId, $period, array $items, $extOrderId = null, $returnUrl = null)
    {
        $driver   = $this->driver();
        $contract = $driver->createContract($buyerId, $period, $items, $extOrderId, $returnUrl);

        $this->dispatch(new ContractCreated($contract, $driver->name()));

        return $contract;
    }

    /**
     * Confirm a contract and emit ContractConfirmed on success.
     *
     * @param int $contractId
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult
     */
    public function confirm($contractId)
    {
        $driver = $this->driver();
        $result = $driver->confirm($contractId);

        if ($result->isOk()) {
            $this->dispatch(new ContractConfirmed($result, $driver->name()));
        }

        return $result;
    }

    /**
     * Cancel a contract and emit ContractCancelled on success.
     *
     * @param int $orderId {@see \Goodoneuz\PayUz\Bnpl\ValueObjects\Contract::orderId()}
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult
     */
    public function cancel($orderId)
    {
        $driver = $this->driver();
        $result = $driver->cancel($orderId);

        if ($result->isOk()) {
            $this->dispatch(new ContractCancelled($result, $driver->name()));
        }

        return $result;
    }

    /**
     * @param int $contractId
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus
     */
    public function status($contractId)
    {
        return $this->driver()->status($contractId);
    }

    // --- internals ---

    /**
     * @param string $name
     * @return BnplDriver
     * @throws BnplException
     */
    protected function resolve($name)
    {
        $driverConfig = isset($this->config['drivers'][$name]) && is_array($this->config['drivers'][$name])
            ? $this->config['drivers'][$name]
            : [];

        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name], $driverConfig, $this->http);
        }

        switch ($name) {
            case 'uzum_nasiya':
                return new UzumNasiyaDriver($driverConfig, $this->http);
            case 'null':
                return new NullBnplDriver();
        }

        throw new BnplException(sprintf('BNPL driver "%s" is not supported.', $name));
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
