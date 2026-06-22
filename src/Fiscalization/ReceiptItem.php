<?php

namespace Goodoneuz\PayUz\Fiscalization;

use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * A single fiscal-receipt line.
 *
 * Amounts are in tiyin (1 som = 100 tiyin) and the unit price is VAT-inclusive
 * (gross), matching how Uzbek retail prices are quoted; the VAT amount carried on
 * the receipt is *extracted* from the gross via {@see Vat::fromGross()}. Quantity
 * is kept as a plain decimal — each driver scales it to its provider's
 * convention (Payme keeps a plain count; soliq fiscal modules expect count×1000).
 *
 * Construct directly or via {@see fromArray()}; call {@see assertValid()} (the
 * manager does this for you) before sending to an OFD.
 */
class ReceiptItem
{
    /** @var string product name as printed on the receipt */
    protected $title;

    /** @var string MXIK / IKPU classification code */
    protected $mxik;

    /** @var string|null package code (упаковка) for the MXIK */
    protected $packageCode;

    /** @var int unit price in tiyin, VAT inclusive */
    protected $price;

    /** @var int|float quantity (decimal allowed for weight/volume) */
    protected $count;

    /** @var int|float VAT rate, per cent */
    protected $vatPercent;

    /** @var int discount for the whole line in tiyin */
    protected $discount;

    /** @var array driver-specific extras merged into the wire item (e.g. CommissionInfo) */
    protected $extra;

    /**
     * @param string         $title
     * @param string|int     $mxik
     * @param int            $price       tiyin, VAT inclusive
     * @param int|float      $count
     * @param int|float      $vatPercent
     * @param string|null    $packageCode
     * @param int            $discount    tiyin
     * @param array          $extra
     */
    public function __construct($title, $mxik, $price, $count = 1, $vatPercent = Vat::RATE_STANDARD, $packageCode = null, $discount = 0, array $extra = [])
    {
        $this->title       = (string) $title;
        $this->mxik        = Mxik::normalize($mxik);
        $this->price       = (int) $price;
        $this->count       = $count + 0; // keep int or float
        $this->vatPercent  = $vatPercent + 0;
        $this->packageCode = ($packageCode === null || $packageCode === '') ? null : (string) $packageCode;
        $this->discount    = (int) $discount;
        $this->extra       = $extra;
    }

    /**
     * Build from an associative array. Both snake_case and a few common aliases
     * are accepted so callers can map straight from their own order rows.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $get = function (array $keys, $default = null) use ($data) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $data) && $data[$key] !== null) {
                    return $data[$key];
                }
            }
            return $default;
        };

        return new self(
            $get(['title', 'name', 'label'], ''),
            $get(['mxik', 'ikpu', 'code', 'spic'], ''),
            $get(['price'], 0),
            $get(['count', 'quantity', 'qty'], 1),
            $get(['vat_percent', 'vatPercent', 'vat'], Vat::RATE_STANDARD),
            $get(['package_code', 'packageCode'], null),
            $get(['discount'], 0),
            (array) $get(['extra'], [])
        );
    }

    /**
     * Line amount before discount, in tiyin.
     *
     * @return int
     */
    public function subtotal()
    {
        return (int) round($this->price * $this->count);
    }

    /**
     * Line amount after discount, in tiyin (never negative).
     *
     * @return int
     */
    public function total()
    {
        $total = $this->subtotal() - $this->discount;

        return $total > 0 ? $total : 0;
    }

    /**
     * VAT amount contained in the (discounted) line total, in tiyin.
     *
     * @return int
     */
    public function vatAmount()
    {
        return Vat::fromGross($this->total(), $this->vatPercent);
    }

    /**
     * Validate this line, throwing on the first problem.
     *
     * @throws InvalidReceiptException
     */
    public function assertValid()
    {
        if (trim($this->title) === '') {
            throw new InvalidReceiptException('Receipt item is missing a title.');
        }

        if (!Mxik::isValid($this->mxik)) {
            throw new InvalidReceiptException(sprintf(
                'Receipt item "%s" has an invalid MXIK/IKPU code (expected %d digits).',
                $this->title,
                Mxik::LENGTH
            ));
        }

        if ($this->price < 0) {
            throw new InvalidReceiptException(sprintf('Receipt item "%s" has a negative price.', $this->title));
        }

        if ($this->count <= 0) {
            throw new InvalidReceiptException(sprintf('Receipt item "%s" must have a positive quantity.', $this->title));
        }

        if (!Vat::isValidRate($this->vatPercent)) {
            throw new InvalidReceiptException(sprintf(
                'Receipt item "%s" has an unsupported VAT rate (%s).',
                $this->title,
                $this->vatPercent
            ));
        }

        if ($this->discount < 0 || $this->discount > $this->subtotal()) {
            throw new InvalidReceiptException(sprintf('Receipt item "%s" has an invalid discount.', $this->title));
        }
    }

    /**
     * Canonical, provider-neutral representation. Drivers map these keys to their
     * own wire format; persistence/debugging can store it verbatim.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'title'        => $this->title,
            'mxik'         => $this->mxik,
            'package_code' => $this->packageCode,
            'price'        => $this->price,
            'count'        => $this->count,
            'vat_percent'  => $this->vatPercent,
            'vat_amount'   => $this->vatAmount(),
            'discount'     => $this->discount,
            'total'        => $this->total(),
        ];
    }

    // --- accessors ---

    public function title()
    {
        return $this->title;
    }

    public function mxik()
    {
        return $this->mxik;
    }

    public function packageCode()
    {
        return $this->packageCode;
    }

    public function price()
    {
        return $this->price;
    }

    public function count()
    {
        return $this->count;
    }

    public function vatPercent()
    {
        return $this->vatPercent;
    }

    public function discount()
    {
        return $this->discount;
    }

    public function extra()
    {
        return $this->extra;
    }
}
