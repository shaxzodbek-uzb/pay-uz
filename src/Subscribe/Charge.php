<?php

namespace Goodoneuz\PayUz\Subscribe;

/**
 * A receipt / charge in the Payme Subscribe flow — the result of
 * `receipts.create` / `receipts.pay` / `receipts.cancel` / `receipts.get`.
 *
 * The lifecycle is driven by the receipt `state`: 0 created → 4 paid, or 5 held
 * (two-stage authorize) → 4 on capture, or 21/50 when cancelling. Amounts are in
 * tiyin. The full gateway object is kept on {@see raw()}.
 */
class Charge
{
    const STATE_CREATED       = 0;
    const STATE_PAID          = 4;
    const STATE_HELD          = 5;
    const STATE_CANCEL_QUEUED = 21;
    const STATE_CANCELLED     = 50;

    /** @var string receipt id (_id) */
    protected $id;

    /** @var int receipt state code */
    protected $state;

    /** @var int amount, tiyin */
    protected $amount;

    /** @var int|null pay time, ms epoch */
    protected $payTime;

    /** @var int|null create time, ms epoch */
    protected $createTime;

    /** @var int|null cancel time, ms epoch */
    protected $cancelTime;

    /** @var string|null masked card number once paid */
    protected $cardNumber;

    /** @var array raw receipt object */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->id         = isset($data['_id']) ? (string) $data['_id'] : (isset($data['id']) ? (string) $data['id'] : '');
        $this->state      = isset($data['state']) ? (int) $data['state'] : self::STATE_CREATED;
        $this->amount     = isset($data['amount']) ? (int) $data['amount'] : 0;
        $this->payTime    = isset($data['pay_time']) ? (int) $data['pay_time'] : null;
        $this->createTime = isset($data['create_time']) ? (int) $data['create_time'] : null;
        $this->cancelTime = isset($data['cancel_time']) ? (int) $data['cancel_time'] : null;
        $this->cardNumber = isset($data['card']['number']) ? (string) $data['card']['number'] : null;
        $this->raw        = $data;
    }

    /**
     * Build from a method result. Accepts the `{receipt:{…}}` envelope or the
     * receipt object directly.
     *
     * @param array $result
     * @return self
     */
    public static function fromResult(array $result)
    {
        $receipt = isset($result['receipt']) && is_array($result['receipt']) ? $result['receipt'] : $result;

        return new self($receipt);
    }

    public function id()
    {
        return $this->id;
    }

    public function state()
    {
        return $this->state;
    }

    public function amount()
    {
        return $this->amount;
    }

    public function isPaid()
    {
        return $this->state === self::STATE_PAID;
    }

    public function isHeld()
    {
        return $this->state === self::STATE_HELD;
    }

    public function isCancelled()
    {
        return $this->state === self::STATE_CANCELLED || $this->state === self::STATE_CANCEL_QUEUED;
    }

    public function payTime()
    {
        return $this->payTime;
    }

    public function cardNumber()
    {
        return $this->cardNumber;
    }

    public function raw()
    {
        return $this->raw;
    }

    /**
     * @return array safe to persist alongside an order
     */
    public function toArray()
    {
        return [
            'id'          => $this->id,
            'state'       => $this->state,
            'amount'      => $this->amount,
            'pay_time'    => $this->payTime,
            'create_time' => $this->createTime,
            'cancel_time' => $this->cancelTime,
            'card_number' => $this->cardNumber,
        ];
    }
}
