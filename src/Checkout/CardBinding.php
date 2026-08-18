<?php

namespace Goodoneuz\PayUz\Checkout;

/**
 * The card details handed to {@see Contracts\CardBinder::startBinding()}.
 *
 * This object exists to make the PAN's lifetime explicit: it is built from the
 * request, passed to a driver, and dropped. Nothing here may be persisted — see
 * {@see BoundCard} for what may.
 *
 *   $binding = CardBinding::make('8600123456789012', '1229', $reference)
 *       ->heldBy('ALISHER KARIMOV')
 *       ->withPhone('+998901112233')
 *       ->withCvc('123')
 *       ->notifyAt(route('cards.octo.callback'));
 *
 * `$expire` is "MMYY", matching {@see Contracts\SubscribeDriver::createCard()};
 * drivers convert to whatever their gateway wants.
 */
class CardBinding
{
    /** @var string full PAN, digits only after normalisation */
    protected $pan;

    /** @var string expiry as "MMYY" */
    protected $expire;

    /** @var string merchant-side binding reference; must be unguessable */
    protected $reference;

    /** @var string|null */
    protected $holderName;

    /** @var string|null phone the confirmation code is sent to */
    protected $phone;

    /** @var string|null */
    protected $cvc;

    /** @var string|null */
    protected $returnUrl;

    /** @var string|null callback URL that will receive the token */
    protected $notifyUrl;

    /** @var string|null callback URL for the binding's own transaction */
    protected $paymentNotifyUrl;

    /** @var array driver-specific extras */
    protected $extra = [];

    /**
     * @param string $pan
     * @param string $expire    "MMYY"
     * @param string $reference merchant-side id for this binding attempt
     */
    public function __construct($pan, $expire, $reference)
    {
        $this->pan       = self::digits($pan);
        $this->expire    = self::digits($expire);
        $this->reference = (string) $reference;
    }

    /**
     * @param string $pan
     * @param string $expire    "MMYY"
     * @param string $reference
     * @return self
     */
    public static function make($pan, $expire, $reference)
    {
        return new self($pan, $expire, $reference);
    }

    /**
     * @param string $name
     * @return self
     */
    public function heldBy($name)
    {
        $this->holderName = $name === null || $name === '' ? null : (string) $name;

        return $this;
    }

    /**
     * Octo accepts one phone shape only: 12 digits, country code included, no `+`
     * and no separators ("998901112233"). Anything else — including the `+998…`
     * form most applications store — is rejected with "Wrong phone format", so the
     * value is normalised here rather than at every call site.
     *
     * A bare 9-digit national number gets the 998 prefix; Octo is Uzbekistan-only.
     *
     * @param string $phone
     * @return self
     */
    public function withPhone($phone)
    {
        $digits = self::digits($phone);

        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        $this->phone = $digits === '' ? null : $digits;

        return $this;
    }

    /**
     * @param string $cvc
     * @return self
     */
    public function withCvc($cvc)
    {
        $cvc = self::digits($cvc);
        $this->cvc = $cvc === '' ? null : $cvc;

        return $this;
    }

    /**
     * @param string $url
     * @return self
     */
    public function returnTo($url)
    {
        $this->returnUrl = $url === null || $url === '' ? null : (string) $url;

        return $this;
    }

    /**
     * @param string $url
     * @return self
     */
    public function notifyAt($url)
    {
        $this->notifyUrl = $url === null || $url === '' ? null : (string) $url;

        return $this;
    }

    /**
     * Where Octo reports the binding's own micro-transaction (`notify_url`), as
     * opposed to {@see notifyAt()}, which receives the card token.
     *
     * Octo requires both, so this exists for applications that route them to
     * different endpoints; leave it unset to use the driver's configured
     * `notify_url`.
     *
     * @param string $url
     * @return self
     */
    public function notifyPaymentsAt($url)
    {
        $this->paymentNotifyUrl = $url === null || $url === '' ? null : (string) $url;

        return $this;
    }

    /**
     * @param array $extra
     * @return self
     */
    public function with(array $extra)
    {
        $this->extra = array_merge($this->extra, $extra);

        return $this;
    }

    public function pan()
    {
        return $this->pan;
    }

    /** @return string "MMYY" */
    public function expire()
    {
        return $this->expire;
    }

    /**
     * Expiry as "YYMM", the order Octo's `exp` field uses.
     *
     * @return string
     */
    public function expireYYMM()
    {
        return strlen($this->expire) === 4
            ? substr($this->expire, 2, 2).substr($this->expire, 0, 2)
            : $this->expire;
    }

    public function reference()
    {
        return $this->reference;
    }

    public function holderName()
    {
        return $this->holderName;
    }

    public function phone()
    {
        return $this->phone;
    }

    public function cvc()
    {
        return $this->cvc;
    }

    public function returnUrl()
    {
        return $this->returnUrl;
    }

    public function notifyUrl()
    {
        return $this->notifyUrl;
    }

    public function paymentNotifyUrl()
    {
        return $this->paymentNotifyUrl;
    }

    public function extra()
    {
        return $this->extra;
    }

    /**
     * The card scheme, derived from the BIN.
     *
     * 5614 is checked before the rest of the 5xx range: it is a Uzcard BIN sitting
     * inside Mastercard's block, and treating it as an international card would
     * send a local card down the wrong rail.
     *
     * @return string 'uzcard' | 'humo' | 'bank_card'
     */
    public function method()
    {
        return self::schemeFor($this->pan);
    }

    /**
     * The same detection, for a card you no longer hold in full — a stored BIN is
     * enough. Keeps callers from re-deriving the scheme (and re-introducing the 5614
     * trap) when charging an already-saved card.
     *
     * @param string $panOrBin full PAN or just its leading digits
     * @return string 'uzcard' | 'humo' | 'bank_card'
     */
    public static function schemeFor($panOrBin)
    {
        $digits = self::digits($panOrBin);

        if (strpos($digits, '8600') === 0 || strpos($digits, '5614') === 0) {
            return 'uzcard';
        }

        if (strpos($digits, '9860') === 0) {
            return 'humo';
        }

        return 'bank_card';
    }

    /**
     * @return string|null first six digits (the BIN), or null when the PAN is short
     */
    public function firstSix()
    {
        return strlen($this->pan) >= 6 ? substr($this->pan, 0, 6) : null;
    }

    /**
     * @return string
     */
    public function lastFour()
    {
        return substr($this->pan, -4);
    }

    /**
     * Keeps the PAN and CVC out of var_dump(), logs and exception traces.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'pan'        => '****'.$this->lastFour(),
            'expire'     => $this->expire,
            'cvc'        => $this->cvc === null ? null : '***',
            'reference'  => $this->reference,
            'holderName' => $this->holderName,
            'phone'      => $this->phone,
        ];
    }

    /**
     * @param string $value
     * @return string
     */
    protected static function digits($value)
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}
