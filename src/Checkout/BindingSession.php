<?php

namespace Goodoneuz\PayUz\Checkout;

/**
 * An in-progress card binding: the gateway has the card and has sent a
 * confirmation code, and this is everything needed to render the code screen and
 * finish the job.
 *
 * {@see secondsLeft()} of 0 means the window closed — the binding cannot be
 * confirmed any more and has to start over.
 */
class BindingSession
{
    /** @var string gateway binding id (Octo: octo_payment_UUID) */
    protected $bindingId;

    /** @var int gateway verification id, replayed on confirm */
    protected $verifyId;

    /** @var string|null masked phone the code went to */
    protected $phone;

    /** @var int seconds the code stays valid */
    protected $secondsLeft;

    /** @var string|null gateway status string, when it reports one */
    protected $status;

    /** @var string|null BIN of the bound card, once the gateway echoes it */
    protected $firstSix;

    /** @var string|null last four digits, once the gateway echoes them */
    protected $lastFour;

    /** @var array raw gateway payload */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->bindingId   = isset($data['binding_id']) ? (string) $data['binding_id'] : '';
        $this->verifyId    = isset($data['verify_id']) ? (int) $data['verify_id'] : 0;
        $this->phone       = isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null;
        $this->secondsLeft = isset($data['seconds_left']) ? (int) $data['seconds_left'] : 0;
        $this->status      = isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : null;
        $this->firstSix    = isset($data['first_six']) && $data['first_six'] !== '' ? (string) $data['first_six'] : null;
        $this->lastFour    = isset($data['last_four']) && $data['last_four'] !== '' ? (string) $data['last_four'] : null;
        $this->raw         = isset($data['raw']) && is_array($data['raw']) ? $data['raw'] : [];
    }

    public function bindingId()
    {
        return $this->bindingId;
    }

    public function verifyId()
    {
        return $this->verifyId;
    }

    /** @return string|null masked, e.g. "99890*****33" */
    public function phone()
    {
        return $this->phone;
    }

    public function secondsLeft()
    {
        return $this->secondsLeft;
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

    public function raw()
    {
        return $this->raw;
    }

    /**
     * Whether the customer can still enter the code.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->secondsLeft > 0;
    }

    /**
     * True once the gateway accepted the code. The card is still not chargeable —
     * the token arrives separately, on the binding callback.
     *
     * @return bool
     */
    public function isConfirmed()
    {
        return in_array(strtolower((string) $this->status), ['succeeded', 'active', 'captured', 'capture'], true);
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return in_array(strtolower((string) $this->status), ['failed', 'canceled', 'cancelled', 'error'], true);
    }

    /**
     * @return array safe to persist — no PAN, no CVC
     */
    public function toArray()
    {
        return [
            'binding_id'   => $this->bindingId,
            'verify_id'    => $this->verifyId,
            'phone'        => $this->phone,
            'seconds_left' => $this->secondsLeft,
            'status'       => $this->status,
            'first_six'    => $this->firstSix,
            'last_four'    => $this->lastFour,
        ];
    }
}
