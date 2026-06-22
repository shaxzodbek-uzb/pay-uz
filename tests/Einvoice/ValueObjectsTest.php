<?php

namespace Goodoneuz\PayUz\Tests\Einvoice;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Einvoice\Som;
use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\InvoiceItem;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\DocumentStatus;
use Goodoneuz\PayUz\Einvoice\Exceptions\InvalidDocumentException;

/**
 * E-invoicing value objects: the tiyin<->decimal-som boundary, the VAT-on-top
 * line math, and document validation.
 */
class ValueObjectsTest extends TestCase
{
    const MXIK = '00702001001000001';

    /** @test */
    public function som_converts_tiyin_to_a_decimal_string_and_back()
    {
        $this->assertSame('1.12', Som::toWire(112));
        $this->assertSame('1.00', Som::toWire(100));
        $this->assertSame('12345.67', Som::toWire(1234567));
        $this->assertSame(112, Som::fromWire('1.12'));
        $this->assertSame(1234567, Som::fromWire('12345.67'));
        $this->assertSame(112, Som::fromWire(Som::toWire(112))); // round-trip
        $this->assertSame('0.01', Som::toWire(1));               // sub-som
        // rounding mode is load-bearing:
        $this->assertSame(1, Som::fromWire('0.005'));
        $this->assertSame(0, Som::fromWire('0.004'));
    }

    /** @test */
    public function vat_amount_rounds_a_fractional_result()
    {
        // 833 tiyin net @12% = 99.96 -> rounds to 100 (not 99 via truncation)
        $item = new InvoiceItem(self::MXIK, 'X', 833, 1, 12);
        $this->assertSame(833, $item->subtotal());
        $this->assertSame(100, $item->vatAmount());
        $this->assertSame(933, $item->total());
    }

    /** @test */
    public function invoice_item_adds_vat_on_top_of_the_net_price()
    {
        // net 1.00 som (100 tiyin) at 12% -> vat 0.12, total 1.12  (the verified example)
        $item = new InvoiceItem(self::MXIK, 'Phone', 100, 1, 12);

        $this->assertSame(100, $item->subtotal());
        $this->assertSame(12, $item->vatAmount());
        $this->assertSame(112, $item->total());
    }

    /** @test */
    public function a_zero_or_without_vat_line_has_no_vat()
    {
        $this->assertSame(0, (new InvoiceItem(self::MXIK, 'Exempt', 100, 1, 0))->vatAmount());
        $this->assertSame(0, (new InvoiceItem(self::MXIK, 'Exempt', 100, 1, 12, ['without_vat' => true]))->vatAmount());
    }

    /** @test */
    public function to_wire_emits_pascalcase_keys_with_decimal_som_strings()
    {
        $line = (new InvoiceItem(self::MXIK, 'Phone', 100, 2, 12))->toWire(1);

        $this->assertSame(1, $line['OrdNo']);
        $this->assertSame(self::MXIK, $line['CatalogCode']);
        $this->assertSame('Phone', $line['Name']);
        $this->assertSame(2, $line['Count']);
        $this->assertSame('1.00', $line['Summa']);               // unit net price (som)
        $this->assertSame('2.00', $line['TotalSumWithoutVat']);  // net line
        $this->assertSame(12, $line['VatRate']);
        $this->assertSame('0.24', $line['VatSum']);
        $this->assertSame('2.24', $line['TotalSum']);
    }

    /** @test */
    public function a_without_vat_line_emits_zero_vat_on_the_wire()
    {
        $line = (new InvoiceItem(self::MXIK, 'Exempt', 100, 1, 12, ['without_vat' => true]))->toWire(1);

        $this->assertTrue($line['WithoutVat']);
        $this->assertSame('0.00', $line['VatSum']);
        $this->assertSame($line['TotalSumWithoutVat'], $line['TotalSum']); // total == net, no VAT added
    }

    /** @test */
    public function canonical_keys_win_over_passthrough_extras()
    {
        // item extra cannot rewrite a computed canonical field
        $line = (new InvoiceItem(self::MXIK, 'Phone', 100, 1, 12, ['extra' => ['Summa' => '9.99', 'Note' => 'x']]))->toWire(1);
        $this->assertSame('1.00', $line['Summa']);  // canonical wins
        $this->assertSame('x', $line['Note']);       // additive extra survives

        // document extra cannot rewrite the seller TIN / product list
        $wire = Document::invoice(new Counterparty('111111111'), new Counterparty('222222222'), [new InvoiceItem(self::MXIK, 'P', 100)])
            ->with(['SellerTin' => 'BOGUS', 'X' => 1])
            ->toWire();
        $this->assertSame('111111111', $wire['SellerTin']);
        $this->assertSame(1, $wire['X']);
    }

    /** @test */
    public function document_status_carries_the_raw_code_and_a_best_effort_label()
    {
        $this->assertTrue((new DocumentStatus([]))->isDraft());                       // missing -> draft default
        $this->assertSame('draft', (new DocumentStatus([]))->label());
        $this->assertSame('wait_for_agent', (new DocumentStatus(['doc_status' => 60]))->label());
        $unknown = new DocumentStatus(['doc_status' => 999]);
        $this->assertSame(999, $unknown->code());                                     // raw int always kept
        $this->assertSame('status_999', $unknown->label());
    }

    /** @test */
    public function from_array_accepts_aliases()
    {
        $item = InvoiceItem::fromArray(['ikpu' => self::MXIK, 'title' => 'Coffee', 'summa' => 250, 'qty' => 3, 'vat' => 0]);
        $this->assertSame(self::MXIK, $item->mxik());
        $this->assertSame(750, $item->subtotal());
        $this->assertSame(0, $item->vatAmount());
    }

    /** @test */
    public function document_to_wire_carries_seller_buyer_and_products()
    {
        $doc = Document::invoice(
            new Counterparty('111111111', 'Seller LLC'),
            new Counterparty('222222222', 'Buyer LLC'),
            [new InvoiceItem(self::MXIK, 'Phone', 100, 1, 12)]
        )->factura('A-1', '2026-06-20');

        $wire = $doc->toWire();
        $this->assertSame('111111111', $wire['SellerTin']);
        $this->assertSame('222222222', $wire['BuyerTin']);
        $this->assertSame('A-1', $wire['FacturaDoc']['FacturaNo']);
        $this->assertSame('1.00', $wire['ProductList']['Products'][0]['Summa']);
    }

    /** @test */
    public function document_validation_rejects_empty_or_untin_documents()
    {
        $seller = new Counterparty('111111111');
        $buyer  = new Counterparty('222222222');

        $this->assertThrowsInvalid(function () use ($seller, $buyer) {
            Document::invoice($seller, $buyer, [])->assertValid(); // no items
        });
        $this->assertThrowsInvalid(function () use ($buyer) {
            Document::invoice(new Counterparty(''), $buyer, [new InvoiceItem(self::MXIK, 'X', 100)])->assertValid(); // no seller TIN
        });
        $this->assertThrowsInvalid(function () use ($seller, $buyer) {
            Document::invoice($seller, $buyer, [new InvoiceItem('123', 'Bad', 100)])->assertValid(); // bad MXIK
        });
    }

    private function assertThrowsInvalid(callable $fn)
    {
        try {
            $fn();
            $this->fail('Expected InvalidDocumentException.');
        } catch (InvalidDocumentException $e) {
            $this->addToAssertionCount(1);
        }
    }
}
