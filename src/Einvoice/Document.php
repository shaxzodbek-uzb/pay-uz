<?php

namespace Goodoneuz\PayUz\Einvoice;

use Goodoneuz\PayUz\Einvoice\Exceptions\InvalidDocumentException;

/**
 * An e-document to register with the operator (an ЭСФ invoice, act, …).
 *
 * The {@see doctype()} selects the kind (002 = ЭСФ invoice). The wire body shape
 * is [INFERRED] from Didox's document schema (the create path is verified, the
 * exact body keys are best-effort) — adjust {@see toWire()} against the sandbox.
 * Amounts come from the {@see InvoiceItem} lines in tiyin; the driver converts.
 */
class Document
{
    const TYPE_INVOICE = '002'; // ЭСФ (электрон счёт-фактура) without an act
    const TYPE_ACT     = '005'; // act of works/services
    const TYPE_WAYBILL = '041'; // ТТН waybill
    const TYPE_CONTRACT = '007';

    /** @var string */
    protected $doctype;

    /** @var Counterparty */
    protected $seller;

    /** @var Counterparty */
    protected $buyer;

    /** @var InvoiceItem[] */
    protected $items;

    /** @var string|null */
    protected $facturaNo;

    /** @var string|null */
    protected $facturaDate;

    /** @var string|null */
    protected $contractNo;

    /** @var string|null */
    protected $contractDate;

    /** @var array receipt-level extras merged into the body */
    protected $extra = [];

    /**
     * @param string                  $doctype
     * @param Counterparty            $seller
     * @param Counterparty            $buyer
     * @param array<InvoiceItem|array> $items
     */
    public function __construct($doctype, Counterparty $seller, Counterparty $buyer, array $items = [])
    {
        $this->doctype = (string) $doctype;
        $this->seller  = $seller;
        $this->buyer   = $buyer;
        $this->items   = [];
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @param array<InvoiceItem|array> $items
     * @return self
     */
    public static function invoice(Counterparty $seller, Counterparty $buyer, array $items = [])
    {
        return new self(self::TYPE_INVOICE, $seller, $buyer, $items);
    }

    /**
     * @param InvoiceItem|array $item
     * @return self
     */
    public function addItem($item)
    {
        if (is_array($item)) {
            $item = InvoiceItem::fromArray($item);
        }
        if (!$item instanceof InvoiceItem) {
            throw new InvalidDocumentException('Invoice items must be InvoiceItem instances or arrays.');
        }
        $this->items[] = $item;

        return $this;
    }

    /**
     * @param string $no
     * @param string $date
     * @return self
     */
    public function factura($no, $date)
    {
        $this->facturaNo   = (string) $no;
        $this->facturaDate = (string) $date;

        return $this;
    }

    /**
     * @param string $no
     * @param string $date
     * @return self
     */
    public function contract($no, $date)
    {
        $this->contractNo   = (string) $no;
        $this->contractDate = (string) $date;

        return $this;
    }

    public function with(array $extra)
    {
        $this->extra = array_merge($this->extra, $extra);

        return $this;
    }

    /**
     * @throws InvalidDocumentException
     */
    public function assertValid()
    {
        if (trim($this->seller->tin()) === '') {
            throw new InvalidDocumentException('Document is missing the seller TIN.');
        }
        if (trim($this->buyer->tin()) === '') {
            throw new InvalidDocumentException('Document is missing the buyer TIN.');
        }
        if (count($this->items) === 0) {
            throw new InvalidDocumentException('Document has no items.');
        }
        $ord = 1;
        foreach ($this->items as $item) {
            $item->assertValid($ord++);
        }
    }

    /**
     * Build the Didox create body. Canonical keys win over extras.
     *
     * @return array
     */
    public function toWire()
    {
        $products = [];
        $ord = 1;
        foreach ($this->items as $item) {
            $products[] = $item->toWire($ord++);
        }

        $canonical = [
            'SellerTin'   => $this->seller->tin(),
            'BuyerTin'    => $this->buyer->tin(),
            'ProductList' => [
                'Tin'      => $this->seller->tin(),
                'HasVat'   => true,
                'Products' => $products,
            ],
        ];
        if ($this->seller->name() !== null) {
            $canonical['SellerName'] = $this->seller->name();
        }
        if ($this->buyer->name() !== null) {
            $canonical['BuyerName'] = $this->buyer->name();
        }
        if ($this->facturaNo !== null) {
            $canonical['FacturaDoc'] = ['FacturaNo' => $this->facturaNo, 'FacturaDate' => $this->facturaDate];
        }
        if ($this->contractNo !== null) {
            $canonical['ContractDoc'] = ['ContractNo' => $this->contractNo, 'ContractDate' => $this->contractDate];
        }

        return array_merge($this->extra, $canonical);
    }

    public function doctype()
    {
        return $this->doctype;
    }

    /** @return InvoiceItem[] */
    public function items()
    {
        return $this->items;
    }
}
