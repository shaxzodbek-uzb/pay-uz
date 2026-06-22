<?php

namespace Goodoneuz\PayUz\Fiscalization;

use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * A fiscal receipt to register with an OFD: one or more {@see ReceiptItem} lines,
 * the order/transaction it settles, whether it is a sale or a refund, and how the
 * customer paid (cash vs card split). All money is in tiyin.
 *
 * Build with the {@see sale()} / {@see refund()} factories and the fluent
 * payment setters:
 *
 *   $receipt = Receipt::sale($order->id, [
 *       new ReceiptItem('Subscription', '00702001001000001', 12000000, 1),
 *   ])->payByCard();
 */
class Receipt
{
    const TYPE_SALE   = 'sale';
    const TYPE_REFUND = 'refund';

    /** @var string */
    protected $type;

    /** @var string merchant order / transaction identifier */
    protected $orderId;

    /** @var ReceiptItem[] */
    protected $items = [];

    /** @var int|null amount paid in cash, tiyin (null until a split is set) */
    protected $cash = null;

    /** @var int|null amount paid by card, tiyin (null until a split is set) */
    protected $card = null;

    /** @var int|null receipt time, unix milliseconds */
    protected $time = null;

    /** @var array driver-specific extras (cashier id, location, …) */
    protected $extra = [];

    /**
     * @param string                  $type    self::TYPE_SALE | self::TYPE_REFUND
     * @param string                  $orderId
     * @param array<ReceiptItem|array> $items
     */
    public function __construct($type, $orderId, array $items = [])
    {
        $this->type    = $type;
        $this->orderId = (string) $orderId;
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @param string                   $orderId
     * @param array<ReceiptItem|array> $items
     * @return self
     */
    public static function sale($orderId, array $items = [])
    {
        return new self(self::TYPE_SALE, $orderId, $items);
    }

    /**
     * @param string                   $orderId
     * @param array<ReceiptItem|array> $items
     * @return self
     */
    public static function refund($orderId, array $items = [])
    {
        return new self(self::TYPE_REFUND, $orderId, $items);
    }

    /**
     * @param ReceiptItem|array $item
     * @return self
     */
    public function addItem($item)
    {
        if (is_array($item)) {
            $item = ReceiptItem::fromArray($item);
        }

        if (!$item instanceof ReceiptItem) {
            throw new InvalidReceiptException('Receipt items must be ReceiptItem instances or arrays.');
        }

        $this->items[] = $item;

        return $this;
    }

    /** Whole receipt paid by card. */
    public function payByCard()
    {
        $this->cash = 0;
        $this->card = $this->total();

        return $this;
    }

    /** Whole receipt paid in cash. */
    public function payByCash()
    {
        $this->cash = $this->total();
        $this->card = 0;

        return $this;
    }

    /**
     * Explicit cash/card split, in tiyin.
     *
     * @param int $cash
     * @param int $card
     * @return self
     */
    public function withPayment($cash, $card)
    {
        $this->cash = (int) $cash;
        $this->card = (int) $card;

        return $this;
    }

    /**
     * @param int $milliseconds unix epoch in ms
     * @return self
     */
    public function at($milliseconds)
    {
        $this->time = (int) $milliseconds;

        return $this;
    }

    /**
     * @param array $extra merged into the receipt-level extras
     * @return self
     */
    public function with(array $extra)
    {
        $this->extra = array_merge($this->extra, $extra);

        return $this;
    }

    /**
     * Sum of line totals, in tiyin.
     *
     * @return int
     */
    public function total()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->total();
        }

        return $total;
    }

    /**
     * Sum of line VAT amounts, in tiyin.
     *
     * @return int
     */
    public function vatTotal()
    {
        $vat = 0;
        foreach ($this->items as $item) {
            $vat += $item->vatAmount();
        }

        return $vat;
    }

    /**
     * Resolved [cash, card] split. Defaults to the whole total on card when no
     * split was set.
     *
     * @return array{0:int,1:int}
     */
    public function payment()
    {
        if ($this->cash === null && $this->card === null) {
            return [0, $this->total()];
        }

        return [(int) $this->cash, (int) $this->card];
    }

    /**
     * Validate the receipt and every line, throwing on the first problem.
     *
     * @throws InvalidReceiptException
     */
    public function assertValid()
    {
        if (!in_array($this->type, [self::TYPE_SALE, self::TYPE_REFUND], true)) {
            throw new InvalidReceiptException(sprintf('Unknown receipt type "%s".', $this->type));
        }

        if (trim($this->orderId) === '') {
            throw new InvalidReceiptException('Receipt is missing an order id.');
        }

        if (count($this->items) === 0) {
            throw new InvalidReceiptException('Receipt has no items.');
        }

        foreach ($this->items as $item) {
            $item->assertValid();
        }

        // If an explicit split was set it must add up to the line total — a
        // mismatch means the receipt would not balance at the OFD.
        if ($this->cash !== null || $this->card !== null) {
            list($cash, $card) = $this->payment();
            if ($cash < 0 || $card < 0) {
                throw new InvalidReceiptException('Receipt payment amounts cannot be negative.');
            }
            if ($cash + $card !== $this->total()) {
                throw new InvalidReceiptException(sprintf(
                    'Receipt payment split (%d cash + %d card) does not match the total (%d).',
                    $cash,
                    $card,
                    $this->total()
                ));
            }
        }
    }

    /**
     * Canonical, provider-neutral representation.
     *
     * @return array
     */
    public function toArray()
    {
        list($cash, $card) = $this->payment();

        return [
            'type'       => $this->type,
            'order_id'   => $this->orderId,
            'time'       => $this->time,
            'items'      => array_map(function (ReceiptItem $item) {
                return $item->toArray();
            }, $this->items),
            'total'      => $this->total(),
            'vat_total'  => $this->vatTotal(),
            'cash'       => $cash,
            'card'       => $card,
            'extra'      => $this->extra,
        ];
    }

    // --- accessors ---

    public function type()
    {
        return $this->type;
    }

    public function isRefund()
    {
        return $this->type === self::TYPE_REFUND;
    }

    public function orderId()
    {
        return $this->orderId;
    }

    /** @return ReceiptItem[] */
    public function items()
    {
        return $this->items;
    }

    public function time()
    {
        return $this->time;
    }

    public function extra()
    {
        return $this->extra;
    }
}
