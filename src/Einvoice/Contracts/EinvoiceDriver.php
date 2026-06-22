<?php

namespace Goodoneuz\PayUz\Einvoice\Contracts;

use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\EinvoiceResult;
use Goodoneuz\PayUz\Einvoice\DocumentStatus;

/**
 * An e-invoicing / e-document operator (Didox is the first driver).
 *
 * The lifecycle for an OUTGOING document: createDocument (draft) -> toSign (get
 * the canonical payload) -> sign it with E-IMZO (outside this package, via a
 * {@see Signer}) -> submit the signed blob (signing IS sending — there is no
 * separate send). Incoming documents are accepted/rejected. State is obtained by
 * {@see status()}/{@see list()} (no webhook exists).
 *
 * The package performs NO cryptography: every signature is supplied by the caller
 * as a pre-signed blob. Synchronous calls throw an
 * {@see \Goodoneuz\PayUz\Einvoice\Exceptions\EinvoiceException} on transport/auth
 * faults and return an {@see EinvoiceResult} otherwise.
 */
interface EinvoiceDriver
{
    /**
     * Authenticate a taxpayer session; the result carries the session token, which
     * the driver then attaches to subsequent calls.
     *
     * @param Counterparty $taxpayer
     * @param string|null  $password  null when authenticating by EDS signature
     * @return EinvoiceResult
     */
    public function login(Counterparty $taxpayer, $password = null);

    /**
     * Create a draft document.
     *
     * @param Document $document
     * @return EinvoiceResult document id + draft status
     */
    public function createDocument(Document $document);

    /**
     * Fetch the canonical base64 payload the caller must sign for an outgoing doc.
     *
     * @param string $documentId
     * @param string $action sign | cancel | reject
     * @return string
     */
    public function toSign($documentId, $action = 'sign');

    /**
     * Submit a pre-signed outgoing document (signing = delivering it).
     *
     * @param string $documentId
     * @param string $signedBlob the PKCS#7 from your signer
     * @return EinvoiceResult
     */
    public function submit($documentId, $signedBlob);

    /**
     * Accept (sign) an incoming document.
     *
     * @param string $documentId
     * @param string $signedBlob
     * @return EinvoiceResult
     */
    public function accept($documentId, $signedBlob);

    /**
     * Reject an incoming document.
     *
     * @param string $documentId
     * @param string $signedBlob
     * @param string $comment
     * @return EinvoiceResult
     */
    public function reject($documentId, $signedBlob, $comment);

    /**
     * Cancel an already-submitted outgoing document.
     *
     * @param string $documentId
     * @param string $signedBlob
     * @return EinvoiceResult
     */
    public function cancel($documentId, $signedBlob);

    /**
     * Delete an unsigned draft (no signature).
     *
     * @param string $documentId
     * @return EinvoiceResult
     */
    public function deleteDraft($documentId);

    /**
     * Poll the document list with filters (owner, status, doctype, dates, …).
     *
     * @param array $filters
     * @return EinvoiceResult result->raw() holds the page of documents
     */
    public function list(array $filters = []);

    /**
     * Single-document state.
     *
     * @param string $documentId
     * @return DocumentStatus
     */
    public function status($documentId);

    /**
     * @return string driver name (e.g. 'didox', 'null')
     */
    public function name();
}
