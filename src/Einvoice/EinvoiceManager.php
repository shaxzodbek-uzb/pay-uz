<?php

namespace Goodoneuz\PayUz\Einvoice;

use Goodoneuz\PayUz\Einvoice\Contracts\Signer;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Einvoice\Drivers\NullDriver;
use Goodoneuz\PayUz\Einvoice\Drivers\DidoxDriver;
use Goodoneuz\PayUz\Support\Http\CurlHttpClient;
use Goodoneuz\PayUz\Einvoice\Signers\NullSigner;
use Goodoneuz\PayUz\Einvoice\Events\DocumentSigned;
use Goodoneuz\PayUz\Einvoice\Events\DocumentCreated;
use Goodoneuz\PayUz\Einvoice\Events\DocumentRejected;
use Goodoneuz\PayUz\Einvoice\Events\DocumentCancelled;
use Goodoneuz\PayUz\Einvoice\Contracts\EinvoiceDriver;
use Goodoneuz\PayUz\Einvoice\Exceptions\EinvoiceException;

/**
 * Resolves and drives e-invoicing drivers — the entry point behind the `Einvoice`
 * facade. Mirrors the other managers, plus a default {@see Signer} and a
 * `signAndSubmit()` convenience that wires the E-IMZO seam (toSign -> sign ->
 * submit) without the package touching a key.
 *
 *   $res = Einvoice::createDocument($document);                 // DocumentCreated
 *   Einvoice::useSigner(new CallableSigner(fn($b64) => $eimzo->sign($b64)));
 *   Einvoice::signAndSubmit($res->documentId());                // DocumentSigned
 */
class EinvoiceManager
{
    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var \Illuminate\Contracts\Events\Dispatcher|null */
    protected $dispatcher;

    /** @var Signer */
    protected $signer;

    /** @var EinvoiceDriver[] */
    protected $drivers = [];

    /** @var callable[] */
    protected $customCreators = [];

    /**
     * @param array           $config
     * @param HttpClient|null $http
     * @param mixed           $dispatcher
     * @param Signer|null     $signer
     */
    public function __construct(array $config = [], ?HttpClient $http = null, $dispatcher = null, ?Signer $signer = null)
    {
        $this->config     = $config;
        $this->http       = $http ?: new CurlHttpClient();
        $this->dispatcher = $dispatcher;
        $this->signer     = $signer ?: new NullSigner();
    }

    /**
     * @param string|null $name
     * @return EinvoiceDriver
     * @throws EinvoiceException for an unknown driver
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
     * @param callable $factory function(array $driverConfig, HttpClient $http): EinvoiceDriver
     * @return self
     */
    public function extend($name, callable $factory)
    {
        $this->customCreators[$name] = $factory;
        unset($this->drivers[$name]);

        return $this;
    }

    /**
     * Set the default E-IMZO signer used by {@see signAndSubmit()}.
     *
     * @param Signer $signer
     * @return self
     */
    public function useSigner(Signer $signer)
    {
        $this->signer = $signer;

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

    public function login(Counterparty $taxpayer, $password = null)
    {
        return $this->driver()->login($taxpayer, $password);
    }

    /**
     * Create a draft and emit DocumentCreated.
     *
     * @param Document $document
     * @return EinvoiceResult
     */
    public function createDocument(Document $document)
    {
        $driver = $this->driver();
        $result = $driver->createDocument($document);
        $this->dispatch(new DocumentCreated($result, $driver->name()));

        return $result;
    }

    public function toSign($documentId, $action = 'sign')
    {
        return $this->driver()->toSign($documentId, $action);
    }

    /**
     * Submit a pre-signed outgoing document and emit DocumentSigned.
     *
     * @param string $documentId
     * @param string $signedBlob
     * @return EinvoiceResult
     */
    public function submit($documentId, $signedBlob)
    {
        $driver = $this->driver();
        $result = $driver->submit($documentId, $signedBlob);
        $this->dispatch(new DocumentSigned($result, $driver->name()));

        return $result;
    }

    /**
     * Fetch the to-sign payload, sign it with the configured signer, and submit —
     * the one-call convenience. Throws SigningException if no signer is configured.
     *
     * @param string      $documentId
     * @param Signer|null $signer override the default signer
     * @return EinvoiceResult
     */
    public function signAndSubmit($documentId, ?Signer $signer = null)
    {
        $driver  = $this->driver();
        $payload = $driver->toSign($documentId);
        $blob    = ($signer ?: $this->signer)->sign($payload);
        $result  = $driver->submit($documentId, $blob);
        $this->dispatch(new DocumentSigned($result, $driver->name()));

        return $result;
    }

    /**
     * Accept an incoming document and emit DocumentSigned.
     */
    public function accept($documentId, $signedBlob)
    {
        $driver = $this->driver();
        $result = $driver->accept($documentId, $signedBlob);
        $this->dispatch(new DocumentSigned($result, $driver->name()));

        return $result;
    }

    /**
     * Reject an incoming document and emit DocumentRejected.
     */
    public function reject($documentId, $signedBlob, $comment)
    {
        $driver = $this->driver();
        $result = $driver->reject($documentId, $signedBlob, $comment);
        $this->dispatch(new DocumentRejected($result, $driver->name()));

        return $result;
    }

    /**
     * Cancel a submitted document and emit DocumentCancelled.
     */
    public function cancel($documentId, $signedBlob)
    {
        $driver = $this->driver();
        $result = $driver->cancel($documentId, $signedBlob);
        $this->dispatch(new DocumentCancelled($result, $driver->name()));

        return $result;
    }

    public function deleteDraft($documentId)
    {
        return $this->driver()->deleteDraft($documentId);
    }

    public function list(array $filters = [])
    {
        return $this->driver()->list($filters);
    }

    public function status($documentId)
    {
        return $this->driver()->status($documentId);
    }

    // --- internals ---

    /**
     * @param string $name
     * @return EinvoiceDriver
     * @throws EinvoiceException
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
            case 'didox':
                return new DidoxDriver($driverConfig, $this->http);
            case 'null':
                return new NullDriver();
        }

        throw new EinvoiceException(sprintf('E-invoice driver "%s" is not supported.', $name));
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
