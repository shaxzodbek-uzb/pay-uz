<?php

namespace Goodoneuz\PayUz\Einvoice;

use Goodoneuz\PayUz\Fiscalization\Mxik;
use Goodoneuz\PayUz\Einvoice\Exceptions\InvalidDocumentException;

/**
 * One line of an e-invoice (a Didox ProductList row).
 *
 * Amounts are tiyin internally (the {@see DidoxDriver} converts to decimal-som on
 * the wire). Unlike a fiscal receipt, ЭСФ prices are VAT-EXCLUSIVE: `price` is the
 * net unit price, VAT is added on top — subtotal = price*count, vat =
 * round(subtotal*rate/100), total = subtotal + vat (e.g. net 1.00 + 12% = 1.12).
 */
class InvoiceItem
{
    /** @var string MXIK / IKPU (17 digits) */
    protected $mxik;

    /** @var string */
    protected $name;

    /** @var string|null */
    protected $catalogName;

    /** @var string|null */
    protected $packageCode;

    /** @var string|null */
    protected $packageName;

    /** @var int net unit price, tiyin */
    protected $price;

    /** @var int|float */
    protected $count;

    /** @var int|float VAT rate per cent */
    protected $vatPercent;

    /** @var bool */
    protected $withoutVat;

    /** @var int|null line number */
    protected $ordNo;

    /** @var array passthrough extras (BarCode, Origin, Marks, …) */
    protected $extra;

    public function __construct($mxik, $name, $price, $count = 1, $vatPercent = 12, array $options = [])
    {
        $this->mxik        = Mxik::normalize($mxik);
        $this->name        = (string) $name;
        $this->price       = (int) $price;
        $this->count       = $count + 0;
        $this->vatPercent  = $vatPercent + 0;
        $this->catalogName = isset($options['catalog_name']) ? (string) $options['catalog_name'] : null;
        $this->packageCode = isset($options['package_code']) && $options['package_code'] !== '' ? (string) $options['package_code'] : null;
        $this->packageName = isset($options['package_name']) ? (string) $options['package_name'] : null;
        $this->withoutVat  = !empty($options['without_vat']) || (float) $this->vatPercent === 0.0;
        $this->ordNo       = isset($options['ord_no']) ? (int) $options['ord_no'] : null;
        $this->extra       = isset($options['extra']) ? (array) $options['extra'] : [];
    }

    /**
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
            $get(['mxik', 'ikpu', 'catalog_code', 'code'], ''),
            $get(['name', 'title'], ''),
            $get(['price', 'summa'], 0),
            $get(['count', 'quantity', 'qty'], 1),
            $get(['vat_percent', 'vat', 'vat_rate'], 12),
            [
                'catalog_name' => $get(['catalog_name']),
                'package_code' => $get(['package_code', 'packagecode']),
                'package_name' => $get(['package_name']),
                'without_vat'  => $get(['without_vat'], false),
                'ord_no'       => $get(['ord_no', 'ordno']),
                'extra'        => (array) $get(['extra'], []),
            ]
        );
    }

    /** @return int net line, tiyin */
    public function subtotal()
    {
        return (int) round($this->price * $this->count);
    }

    /** @return int VAT added on top, tiyin */
    public function vatAmount()
    {
        if ($this->withoutVat) {
            return 0;
        }

        return (int) round($this->subtotal() * $this->vatPercent / 100);
    }

    /** @return int line total incl. VAT, tiyin */
    public function total()
    {
        return $this->subtotal() + $this->vatAmount();
    }

    /**
     * @param int $ordNo
     * @throws InvalidDocumentException
     */
    public function assertValid($ordNo)
    {
        if (!Mxik::isValid($this->mxik)) {
            throw new InvalidDocumentException(sprintf('Invoice item "%s" has an invalid MXIK (expected %d digits).', $this->name, Mxik::LENGTH));
        }
        if (trim($this->name) === '') {
            throw new InvalidDocumentException('Invoice item is missing a name.');
        }
        if ($this->count <= 0) {
            throw new InvalidDocumentException(sprintf('Invoice item "%s" must have a positive quantity.', $this->name));
        }
        if ($this->price < 0) {
            throw new InvalidDocumentException(sprintf('Invoice item "%s" has a negative price.', $this->name));
        }
    }

    /**
     * Didox ProductList row (PascalCase, decimal-som strings).
     *
     * @param int $ordNo default line number when not set
     * @return array
     */
    public function toWire($ordNo)
    {
        $line = [
            'OrdNo'              => $this->ordNo !== null ? $this->ordNo : $ordNo,
            'CatalogCode'        => $this->mxik,
            'Name'               => $this->name,
            'Count'              => $this->count,
            'Summa'              => Som::toWire($this->price),
            'TotalSumWithoutVat' => Som::toWire($this->subtotal()),
            'VatRate'            => $this->vatPercent,
            'VatSum'             => Som::toWire($this->vatAmount()),
            'TotalSum'           => Som::toWire($this->total()),
            'WithoutVat'         => $this->withoutVat,
        ];
        if ($this->catalogName !== null) {
            $line['CatalogName'] = $this->catalogName;
        }
        if ($this->packageCode !== null) {
            $line['PackageCode'] = $this->packageCode;
        }
        if ($this->packageName !== null) {
            $line['PackageName'] = $this->packageName;
        }

        return array_merge($this->extra, $line);
    }

    public function mxik()
    {
        return $this->mxik;
    }

    public function name()
    {
        return $this->name;
    }
}
