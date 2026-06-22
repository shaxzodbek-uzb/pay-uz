<?php

namespace Goodoneuz\PayUz\Tests\Einvoice;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\InvoiceItem;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\EinvoiceManager;
use Goodoneuz\PayUz\Einvoice\Drivers\NullDriver;
use Goodoneuz\PayUz\Einvoice\Signers\NullSigner;
use Goodoneuz\PayUz\Einvoice\Signers\CallableSigner;
use Goodoneuz\PayUz\Einvoice\Events\DocumentSigned;
use Goodoneuz\PayUz\Einvoice\Events\DocumentCreated;
use Goodoneuz\PayUz\Einvoice\Events\DocumentRejected;
use Goodoneuz\PayUz\Einvoice\Events\DocumentCancelled;
use Goodoneuz\PayUz\Tests\Support\RecordingDispatcher;
use Goodoneuz\PayUz\Einvoice\Exceptions\SigningException;
use Goodoneuz\PayUz\Einvoice\Exceptions\EinvoiceException;

/**
 * Manager: driver resolution + extend(), the event-emitting helpers, and the
 * E-IMZO signer seam (signAndSubmit).
 */
class EinvoiceManagerTest extends TestCase
{
    const MXIK = '00702001001000001';

    private function document()
    {
        return Document::invoice(
            new Counterparty('111111111'),
            new Counterparty('222222222'),
            [new InvoiceItem(self::MXIK, 'Phone', 100, 1, 12)]
        );
    }

    /** @test */
    public function it_resolves_the_default_driver_and_unknown_throws()
    {
        $this->assertInstanceOf(NullDriver::class, (new EinvoiceManager(['default' => 'null']))->driver());
        $this->assertSame('null', (new EinvoiceManager([]))->defaultDriver());

        $this->expectException(EinvoiceException::class);
        (new EinvoiceManager([]))->driver('nope');
    }

    /** @test */
    public function lifecycle_helpers_emit_their_events()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new EinvoiceManager(['default' => 'null'], null, $dispatcher);

        $created = $manager->createDocument($this->document());
        $manager->submit($created->documentId(), 'blob');
        $manager->accept($created->documentId(), 'blob');
        $manager->reject($created->documentId(), 'blob', 'nope');
        $manager->cancel($created->documentId(), 'blob');

        $this->assertCount(1, $dispatcher->ofType(DocumentCreated::class));
        $this->assertCount(2, $dispatcher->ofType(DocumentSigned::class));   // submit + accept
        $this->assertCount(1, $dispatcher->ofType(DocumentRejected::class));
        $this->assertCount(1, $dispatcher->ofType(DocumentCancelled::class));
    }

    /** @test */
    public function sign_and_submit_without_a_signer_throws()
    {
        $manager = new EinvoiceManager(['default' => 'null']); // default signer is NullSigner

        $this->expectException(SigningException::class);
        $manager->signAndSubmit('doc-1');
    }

    /** @test */
    public function sign_and_submit_uses_the_configured_signer_and_emits_signed()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new EinvoiceManager(['default' => 'null'], null, $dispatcher);

        $seen = null;
        $manager->useSigner(new CallableSigner(function ($payload) use (&$seen) {
            $seen = $payload;
            return 'SIGNED('.$payload.')';
        }));

        $result = $manager->signAndSubmit('doc-1');

        // the signer received the driver's to-sign payload
        $this->assertSame(base64_encode('null-to-sign:doc-1'), $seen);
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $dispatcher->ofType(DocumentSigned::class));
    }

    /** @test */
    public function an_explicit_signer_overrides_the_default()
    {
        $manager = new EinvoiceManager(['default' => 'null']);
        $manager->useSigner(new NullSigner()); // default would throw

        // ... but an explicit per-call signer is used instead
        $result = $manager->signAndSubmit('doc-1', new CallableSigner(function () {
            return 'BLOB';
        }));

        $this->assertTrue($result->isSuccessful());
    }
}
