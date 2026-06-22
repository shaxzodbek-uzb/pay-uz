<?php

namespace Goodoneuz\PayUz\Einvoice\Signers;

use Goodoneuz\PayUz\Einvoice\Contracts\Signer;

/**
 * Adapts a caller-supplied callable into a {@see Signer}, so a host app can wire
 * its E-IMZO integration as a closure without a dedicated class:
 *
 *   new CallableSigner(function ($base64) { return $myEimzo->signPkcs7($base64); });
 */
class CallableSigner implements Signer
{
    /** @var callable */
    protected $callback;

    /**
     * @param callable $callback function(string $base64Payload): string
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function sign($base64Payload)
    {
        return (string) call_user_func($this->callback, $base64Payload);
    }
}
