<?php

namespace Goodoneuz\PayUz\Fiscalization\Drivers;

use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;
use Goodoneuz\PayUz\Fiscalization\Contracts\FiscalDriver;

/**
 * A no-op fiscalization driver — the safe default on a fresh install.
 *
 * It validates the receipt (so integration bugs surface in development) and
 * returns a deterministic successful {@see FiscalResult} with a synthetic fiscal
 * sign, without contacting any OFD. Optionally logs each receipt. Use it for
 * local development and tests, then switch `fiscalization.default` to a real
 * driver in production.
 */
class NullDriver implements FiscalDriver
{
    /** @var bool */
    protected $log;

    /**
     * @param array $config ['log' => bool]
     */
    public function __construct(array $config = [])
    {
        $this->log = !empty($config['log']);
    }

    /**
     * {@inheritdoc}
     */
    public function fiscalize(Receipt $receipt)
    {
        $receipt->assertValid();

        if ($this->log && function_exists('logger')) {
            logger()->info('[pay-uz] fiscalization (null driver)', $receipt->toArray());
        }

        // Deterministic synthetic sign so tests/dev flows have something stable.
        $sign = strtoupper(substr(md5($receipt->type().'|'.$receipt->orderId().'|'.$receipt->total()), 0, 16));

        return FiscalResult::success([
            'receipt_id'  => 'null-'.$receipt->orderId(),
            'fiscal_sign' => $sign,
            'qr'          => null,
            'receipt_url' => null,
            'terminal_id' => 'NULL-TERMINAL',
            'raw'         => ['driver' => 'null', 'receipt' => $receipt->toArray()],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'null';
    }
}
