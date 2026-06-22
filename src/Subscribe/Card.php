<?php

namespace Goodoneuz\PayUz\Subscribe;

/**
 * A tokenized card returned by the gateway.
 *
 * SECURITY: the only value safe (and legal) to persist is {@see token()} — never
 * store the PAN. The `number` here is already masked by the gateway
 * (e.g. "860006******6311") and `expire` is the gateway's rendered form
 * ("MM/YY"). A card is chargeable only once {@see isVerified()} is true (and, for
 * subscriptions, {@see isRecurrent()} as well).
 */
class Card
{
    /** @var string masked PAN */
    protected $number;

    /** @var string expiry as returned by the gateway (MM/YY) */
    protected $expire;

    /** @var string opaque token — the only persistable identifier */
    protected $token;

    /** @var bool reusable/recurrent token (save=true) */
    protected $recurrent;

    /** @var bool OTP confirmed */
    protected $verify;

    /** @var string|null card scheme (e.g. UZCARD/HUMO), when provided */
    protected $type;

    /** @var array raw card object */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->number    = isset($data['number'])    ? (string) $data['number'] : '';
        $this->expire    = isset($data['expire'])    ? (string) $data['expire'] : '';
        $this->token     = isset($data['token'])     ? (string) $data['token']  : '';
        $this->recurrent = !empty($data['recurrent']);
        $this->verify    = !empty($data['verify']);
        $this->type      = isset($data['type']) && $data['type'] !== '' ? (string) $data['type'] : null;
        $this->raw       = $data;
    }

    /**
     * Build from a method result. Accepts either the `{card:{…}}` envelope or the
     * card object directly.
     *
     * @param array $result
     * @return self
     */
    public static function fromResult(array $result)
    {
        $card = isset($result['card']) && is_array($result['card']) ? $result['card'] : $result;

        return new self($card);
    }

    public function number()
    {
        return $this->number;
    }

    public function expire()
    {
        return $this->expire;
    }

    public function token()
    {
        return $this->token;
    }

    public function isRecurrent()
    {
        return $this->recurrent;
    }

    public function isVerified()
    {
        return $this->verify;
    }

    public function type()
    {
        return $this->type;
    }

    public function raw()
    {
        return $this->raw;
    }

    /**
     * @return array safe to persist (no PAN beyond the gateway's mask)
     */
    public function toArray()
    {
        return [
            'number'    => $this->number,
            'expire'    => $this->expire,
            'token'     => $this->token,
            'recurrent' => $this->recurrent,
            'verify'    => $this->verify,
            'type'      => $this->type,
        ];
    }
}
