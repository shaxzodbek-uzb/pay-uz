<?php

namespace Goodoneuz\PayUz\Subscribe;

/**
 * Result of requesting an OTP for a card token (Payme `cards.get_verify_code`):
 * whether the SMS was sent, the masked phone it went to, and how long to wait
 * (milliseconds) before a resend is allowed.
 */
class VerifyCode
{
    /** @var bool */
    protected $sent;

    /** @var string masked phone, e.g. "99890*****31" */
    protected $phone;

    /** @var int resend throttle, milliseconds */
    protected $wait;

    /** @var array */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->sent  = !empty($data['sent']);
        $this->phone = isset($data['phone']) ? (string) $data['phone'] : '';
        $this->wait  = isset($data['wait']) ? (int) $data['wait'] : 0;
        $this->raw   = $data;
    }

    /**
     * @param array $result
     * @return self
     */
    public static function fromResult(array $result)
    {
        return new self($result);
    }

    public function wasSent()
    {
        return $this->sent;
    }

    public function phone()
    {
        return $this->phone;
    }

    /** @return int milliseconds to wait before a resend */
    public function wait()
    {
        return $this->wait;
    }

    public function raw()
    {
        return $this->raw;
    }
}
