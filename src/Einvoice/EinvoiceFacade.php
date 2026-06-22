<?php

namespace Goodoneuz\PayUz\Einvoice;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Goodoneuz\PayUz\Einvoice\Contracts\EinvoiceDriver driver(string $name = null)
 * @method static EinvoiceManager extend(string $name, callable $factory)
 * @method static EinvoiceManager useSigner(\Goodoneuz\PayUz\Einvoice\Contracts\Signer $signer)
 * @method static string defaultDriver()
 * @method static EinvoiceResult login(Counterparty $taxpayer, string $password = null)
 * @method static EinvoiceResult createDocument(Document $document)
 * @method static string toSign(string $documentId, string $action = 'sign')
 * @method static EinvoiceResult submit(string $documentId, string $signedBlob)
 * @method static EinvoiceResult signAndSubmit(string $documentId, \Goodoneuz\PayUz\Einvoice\Contracts\Signer $signer = null)
 * @method static EinvoiceResult accept(string $documentId, string $signedBlob)
 * @method static EinvoiceResult reject(string $documentId, string $signedBlob, string $comment)
 * @method static EinvoiceResult cancel(string $documentId, string $signedBlob)
 * @method static EinvoiceResult deleteDraft(string $documentId)
 * @method static EinvoiceResult list(array $filters = [])
 * @method static DocumentStatus status(string $documentId)
 *
 * @see EinvoiceManager
 */
class EinvoiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pay-uz-einvoice';
    }
}
