<?php

namespace Goodoneuz\PayUz\Tests\Einvoice;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\InvoiceItem;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\Drivers\NullDriver;

/**
 * The no-op e-invoicing driver: validates the document and returns inert results.
 */
class NullDriverTest extends TestCase
{
    /** @test */
    public function it_walks_the_document_lifecycle_without_network_or_crypto()
    {
        $driver = new NullDriver();

        $this->assertNotEmpty($driver->login(new Counterparty('111111111'))->token());

        $doc = Document::invoice(
            new Counterparty('111111111'),
            new Counterparty('222222222'),
            [new InvoiceItem('00702001001000001', 'Phone', 100, 1, 12)]
        );
        $created = $driver->createDocument($doc);
        $this->assertTrue($created->isSuccessful());
        $this->assertNotEmpty($created->documentId());

        $this->assertNotEmpty($driver->toSign($created->documentId()));
        $this->assertTrue($driver->submit($created->documentId(), 'blob')->isSuccessful());
        $this->assertSame('null', $driver->name());
    }
}
