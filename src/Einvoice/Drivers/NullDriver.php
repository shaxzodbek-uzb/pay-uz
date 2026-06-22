<?php

namespace Goodoneuz\PayUz\Einvoice\Drivers;

use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\EinvoiceResult;
use Goodoneuz\PayUz\Einvoice\DocumentStatus;
use Goodoneuz\PayUz\Einvoice\Contracts\EinvoiceDriver;

/**
 * A no-op e-invoicing driver — the safe default on a fresh install. It validates
 * the document and returns inert successful results without contacting any
 * operator or signer. Use it for local development and tests, then switch
 * `einvoice.default` to 'didox' in production.
 */
class NullDriver implements EinvoiceDriver
{
    public function login(Counterparty $taxpayer, $password = null)
    {
        return EinvoiceResult::success(['token' => 'null-session-'.substr(md5($taxpayer->tin()), 0, 12)]);
    }

    public function createDocument(Document $document)
    {
        $document->assertValid();

        return EinvoiceResult::success([
            'document_id' => 'null-'.substr(md5(json_encode($document->toWire())), 0, 16),
            'status'      => DocumentStatus::DRAFT,
        ]);
    }

    public function toSign($documentId, $action = 'sign')
    {
        return base64_encode('null-to-sign:'.$documentId);
    }

    public function submit($documentId, $signedBlob)
    {
        return EinvoiceResult::success(['document_id' => (string) $documentId, 'status' => DocumentStatus::SIGNED_ONE_PARTY]);
    }

    public function accept($documentId, $signedBlob)
    {
        return EinvoiceResult::success(['document_id' => (string) $documentId, 'status' => DocumentStatus::SIGNED_ONE_PARTY]);
    }

    public function reject($documentId, $signedBlob, $comment)
    {
        return EinvoiceResult::success(['document_id' => (string) $documentId, 'status' => DocumentStatus::REJECTED]);
    }

    public function cancel($documentId, $signedBlob)
    {
        return EinvoiceResult::success(['document_id' => (string) $documentId, 'status' => DocumentStatus::REJECTED]);
    }

    public function deleteDraft($documentId)
    {
        return EinvoiceResult::success(['document_id' => (string) $documentId]);
    }

    public function list(array $filters = [])
    {
        return EinvoiceResult::success(['raw' => ['documents' => []]]);
    }

    public function status($documentId)
    {
        return new DocumentStatus(['document_id' => (string) $documentId, 'doc_status' => DocumentStatus::SIGNED_ONE_PARTY]);
    }

    public function name()
    {
        return 'null';
    }
}
