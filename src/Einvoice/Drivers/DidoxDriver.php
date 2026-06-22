<?php

namespace Goodoneuz\PayUz\Einvoice\Drivers;

use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\EinvoiceResult;
use Goodoneuz\PayUz\Einvoice\DocumentStatus;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Einvoice\Contracts\EinvoiceDriver;
use Goodoneuz\PayUz\Einvoice\Exceptions\EinvoiceException;

/**
 * Didox (didox.uz) e-invoicing driver, mapped to Didox's partner REST API.
 *
 * Auth has two layers: a partner credential header (`Partner-Authorization` by
 * default — switchable via `partner_header` because the docs/SDKs disagree) sent
 * on every call, and a per-company `user-key` session token obtained from
 * {@see login()} (or set via config `user_key`). Base host selects prod vs
 * sandbox via `base_url`.
 *
 * The driver performs NO cryptography: signed operations take a caller-supplied
 * PKCS#7 blob. Amounts reach the wire as decimal-som strings via the {@see \Goodoneuz\PayUz\Einvoice\Som}
 * helper inside {@see \Goodoneuz\PayUz\Einvoice\InvoiceItem}.
 *
 * Several wire details (response envelope keys, the exact sign/accept field name,
 * the to-sign source path) are best-effort from the partner docs + a community
 * SDK and are flagged UNCERTAIN; confirm against the sandbox.
 */
class DidoxDriver implements EinvoiceDriver
{
    const BASE_URL = 'https://api-partners.didox.uz';

    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var string|null session token from login() */
    protected $userKey;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function login(Counterparty $taxpayer, $password = null)
    {
        $path = '/v1/auth/'.rawurlencode($taxpayer->tin()).'/password/'.$this->locale();
        $body = $this->post($path, ['password' => (string) $password], $this->partnerHeaders());

        $this->userKey = $this->pick($body, ['user-key', 'userKey', 'token', 'key']);

        return EinvoiceResult::success(['token' => $this->userKey, 'raw' => $body]);
    }

    public function createDocument(Document $document)
    {
        $document->assertValid();

        $path = '/v1/documents/'.rawurlencode($document->doctype()).'/create/'.$this->locale();
        $body = $this->post($path, $document->toWire(), $this->sessionHeaders());

        return EinvoiceResult::success([
            'document_id' => $this->pick($body, ['_id', 'id', 'document_id']),
            'status'      => $this->pick($body, ['doc_status']),
            'raw'         => $body,
        ]);
    }

    public function toSign($documentId, $action = 'sign')
    {
        // The canonical base64 payload to sign for this document.
        $body = $this->get('/v1/documents/'.rawurlencode($documentId).'/documentBase64', $this->sessionHeaders());

        return (string) $this->pick($body, ['documentB64', 'pkcs7B64', 'base64', 'data']);
    }

    public function submit($documentId, $signedBlob)
    {
        return $this->signed($documentId, $signedBlob);
    }

    public function accept($documentId, $signedBlob)
    {
        // Accepting an incoming document is the same /sign call as submitting.
        return $this->signed($documentId, $signedBlob);
    }

    public function reject($documentId, $signedBlob, $comment)
    {
        $body = $this->post('/v1/documents/'.rawurlencode($documentId).'/reject', [
            'comment'   => (string) $comment,
            'signature' => (string) $signedBlob,
        ], $this->sessionHeaders());

        return $this->resultFrom($documentId, $body);
    }

    public function cancel($documentId, $signedBlob)
    {
        $body = $this->post('/v1/documents/'.rawurlencode($documentId).'/delete', [
            'signature' => (string) $signedBlob,
        ], $this->sessionHeaders());

        return $this->resultFrom($documentId, $body);
    }

    public function deleteDraft($documentId)
    {
        $body = $this->post('/v1/documents/'.rawurlencode($documentId).'/delete/draft', [], $this->sessionHeaders());

        return $this->resultFrom($documentId, $body);
    }

    public function list(array $filters = [])
    {
        $query = $filters ? '?'.http_build_query($filters) : '';
        $body  = $this->get('/v2/documents'.$query, $this->sessionHeaders());

        return EinvoiceResult::success(['raw' => $body]);
    }

    public function status($documentId)
    {
        $body = $this->get('/v1/documents/'.rawurlencode($documentId), $this->sessionHeaders());
        $doc  = isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
        $doc['document_id'] = isset($doc['_id']) ? $doc['_id'] : $documentId;

        return new DocumentStatus($doc);
    }

    public function name()
    {
        return 'didox';
    }

    // --- internals ---

    /**
     * @param string $documentId
     * @param string $signedBlob
     * @return EinvoiceResult
     */
    protected function signed($documentId, $signedBlob)
    {
        $body = $this->post('/v1/documents/'.rawurlencode($documentId).'/sign', [
            'signature' => (string) $signedBlob,
        ], $this->sessionHeaders());

        return $this->resultFrom($documentId, $body);
    }

    /**
     * @param string $documentId
     * @param array  $body
     * @return EinvoiceResult
     */
    protected function resultFrom($documentId, array $body)
    {
        return EinvoiceResult::success([
            'document_id' => $documentId,
            'status'      => $this->pick($body, ['doc_status']),
            'raw'         => $body,
        ]);
    }

    /**
     * @param string $path
     * @param array  $payload
     * @param array  $headers
     * @return array
     * @throws EinvoiceException
     */
    protected function post($path, array $payload, array $headers)
    {
        $this->assertConfigured();

        return $this->parse($this->http->post($this->baseUrl().$path, $payload, $headers));
    }

    /**
     * @param string $path
     * @param array  $headers
     * @return array
     * @throws EinvoiceException
     */
    protected function get($path, array $headers)
    {
        $this->assertConfigured();

        return $this->parse($this->http->request('GET', $this->baseUrl().$path, null, $headers));
    }

    /**
     * Throw on a non-2xx (auth/transport/validation); otherwise return the body.
     *
     * @param array $response
     * @return array
     * @throws EinvoiceException
     */
    protected function parse(array $response)
    {
        $status = isset($response['status']) ? (int) $response['status'] : 0;
        $body   = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        if ($status < 200 || $status >= 300) {
            $message = $this->pick($body, ['message', 'error', 'error_msg', 'detail']);

            throw new EinvoiceException($message ? (string) $message : 'Didox HTTP '.$status.'.', $status, $body);
        }

        return $body;
    }

    /**
     * @return array
     */
    protected function partnerHeaders()
    {
        return [$this->cfg('partner_header', 'Partner-Authorization') => (string) $this->cfg('partner_token', '')];
    }

    /**
     * @return array
     * @throws EinvoiceException
     */
    protected function sessionHeaders()
    {
        $key = $this->userKey ?: $this->cfg('user_key');
        if (!$key) {
            throw new EinvoiceException('Didox session is not established; call login() first or set "user_key" in config.');
        }

        return array_merge($this->partnerHeaders(), ['user-key' => (string) $key]);
    }

    /**
     * @throws EinvoiceException
     */
    protected function assertConfigured()
    {
        if (!$this->cfg('partner_token')) {
            throw new EinvoiceException('Didox driver is not configured: missing "partner_token".');
        }
    }

    /**
     * @return string
     */
    protected function baseUrl()
    {
        return rtrim($this->cfg('base_url', self::BASE_URL), '/');
    }

    /**
     * @return string
     */
    protected function locale()
    {
        return (string) $this->cfg('locale', 'ru');
    }

    /**
     * @param array $body
     * @param array $keys
     * @return mixed|null
     */
    protected function pick(array $body, array $keys)
    {
        $haystacks = [$body];
        if (isset($body['data']) && is_array($body['data'])) {
            $haystacks[] = $body['data'];
        }
        foreach ($haystacks as $haystack) {
            foreach ($keys as $key) {
                if (isset($haystack[$key]) && $haystack[$key] !== '') {
                    return $haystack[$key];
                }
            }
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function cfg($key, $default = null)
    {
        return isset($this->config[$key]) && $this->config[$key] !== '' ? $this->config[$key] : $default;
    }
}
