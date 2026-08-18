<?php

namespace Goodoneuz\PayUz\Checkout;

/**
 * The outcome of a binding, as delivered on the gateway's callback: either a
 * usable token or a refusal.
 *
 * The callback body may contain a full PAN. This object never exposes one — only
 * the BIN and the last four digits, derived on the way in — so persisting
 * {@see toArray()} cannot leak a card number.
 *
 * Answer the callback request with HTTP 200 and {@see acknowledgement()}. Octo
 * retries three times and then **cancels the token** if it never gets that reply,
 * so acknowledge even a callback you could not match to anything.
 */
class BoundCard
{
    /** @var string|null the only persistable identifier */
    protected $token;

    /** @var string merchant-side binding reference, i.e. CardBinding::reference() */
    protected $reference;

    /** @var string gateway status ('active' when bound) */
    protected $status;

    /** @var string|null */
    protected $firstSix;

    /** @var string|null */
    protected $lastFour;

    /** @var string|null "MMYY" as echoed by the gateway */
    protected $expire;

    /** @var string|null */
    protected $holderName;

    /** @var array raw callback payload, with any PAN stripped */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->token      = isset($data['token']) && $data['token'] !== '' ? (string) $data['token'] : null;
        $this->reference  = isset($data['reference']) ? (string) $data['reference'] : '';
        $this->status     = isset($data['status']) ? strtolower((string) $data['status']) : '';
        $this->firstSix   = isset($data['first_six']) && $data['first_six'] !== '' ? (string) $data['first_six'] : null;
        $this->lastFour   = isset($data['last_four']) && $data['last_four'] !== '' ? (string) $data['last_four'] : null;
        $this->expire     = isset($data['expire']) && $data['expire'] !== '' ? (string) $data['expire'] : null;
        $this->holderName = isset($data['holder_name']) && $data['holder_name'] !== '' ? (string) $data['holder_name'] : null;
        $this->raw        = isset($data['raw']) && is_array($data['raw']) ? $data['raw'] : [];
    }

    public function token()
    {
        return $this->token;
    }

    public function reference()
    {
        return $this->reference;
    }

    public function status()
    {
        return $this->status;
    }

    public function firstSix()
    {
        return $this->firstSix;
    }

    public function lastFour()
    {
        return $this->lastFour;
    }

    public function expire()
    {
        return $this->expire;
    }

    public function holderName()
    {
        return $this->holderName;
    }

    public function raw()
    {
        return $this->raw;
    }

    /**
     * A card is only usable when the gateway said so AND handed over a token.
     *
     * @return bool
     */
    public function isBound()
    {
        return $this->status === 'active' && $this->token !== null;
    }

    /**
     * The body the gateway expects in reply to its callback.
     *
     * @return array
     */
    public static function acknowledgement()
    {
        return ['status' => 'success', 'message' => 'Callback processed successfully'];
    }

    /**
     * @return array safe to persist — token plus a masked view of the card
     */
    public function toArray()
    {
        return [
            'token'       => $this->token,
            'reference'   => $this->reference,
            'status'      => $this->status,
            'first_six'   => $this->firstSix,
            'last_four'   => $this->lastFour,
            'expire'      => $this->expire,
            'holder_name' => $this->holderName,
        ];
    }
}
