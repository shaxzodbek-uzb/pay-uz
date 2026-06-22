<?php

namespace Goodoneuz\PayUz\Tests\Einvoice;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Einvoice\Document;
use Goodoneuz\PayUz\Einvoice\InvoiceItem;
use Goodoneuz\PayUz\Einvoice\Counterparty;
use Goodoneuz\PayUz\Einvoice\DocumentStatus;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Einvoice\Drivers\DidoxDriver;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Einvoice\Exceptions\EinvoiceException;

/**
 * Didox e-invoicing driver: auth headers, the document lifecycle endpoints, the
 * decimal-som body, and error handling — all against a fake transport.
 */
class DidoxDriverTest extends TestCase
{
    const MXIK = '00702001001000001';

    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        $config = array_merge([
            'base_url' => 'https://testapi3.didox.uz', 'partner_token' => 'PT', 'user_key' => 'UK', 'locale' => 'ru',
        ], $overrides);

        return new DidoxDriver($config, $http);
    }

    private function document()
    {
        return Document::invoice(
            new Counterparty('111111111', 'Seller'),
            new Counterparty('222222222', 'Buyer'),
            [new InvoiceItem(self::MXIK, 'Phone', 100, 1, 12)]
        );
    }

    /** @test */
    public function login_posts_to_the_auth_path_with_only_the_partner_header_and_stores_the_session()
    {
        $http = (new FakeHttpClient())
            ->queue(['user-key' => 'SESSION-1'])                                  // login
            ->queue(['_id' => 'doc-1', 'doc_status' => 0]);                       // createDocument

        // no user_key in config -> the session must come from login()
        $driver = $this->driver($http, ['user_key' => null]);

        $result = $driver->login(new Counterparty('111111111'), 'secret');
        $this->assertSame('https://testapi3.didox.uz/v1/auth/111111111/password/ru', $http->requests[0]['url']);
        $this->assertSame('PT', $http->requests[0]['headers']['Partner-Authorization']);
        $this->assertArrayNotHasKey('user-key', $http->requests[0]['headers']); // login has no session yet
        $this->assertSame('SESSION-1', $result->token());

        // subsequent call carries the session token obtained from login
        $driver->createDocument($this->document());
        $this->assertSame('SESSION-1', $http->requests[1]['headers']['user-key']);
    }

    /** @test */
    public function create_document_posts_the_doctype_path_with_both_auth_headers_and_som_amounts()
    {
        $http = (new FakeHttpClient())->queue(['_id' => 'doc-9', 'doc_status' => 0]);

        $result = $this->driver($http)->createDocument($this->document());

        $req = $http->lastRequest;
        $this->assertSame('https://testapi3.didox.uz/v1/documents/002/create/ru', $req['url']);
        $this->assertSame('PT', $req['headers']['Partner-Authorization']);
        $this->assertSame('UK', $req['headers']['user-key']);
        $this->assertSame('111111111', $req['payload']['SellerTin']);
        $this->assertSame('1.00', $req['payload']['ProductList']['Products'][0]['Summa']); // decimal som, not tiyin
        $this->assertSame('0.12', $req['payload']['ProductList']['Products'][0]['VatSum']);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('doc-9', $result->documentId());
    }

    /** @test */
    public function to_sign_gets_the_document_base64()
    {
        $http = (new FakeHttpClient())->queue(['documentB64' => 'BASE64PAYLOAD']);

        $payload = $this->driver($http)->toSign('doc-9');

        $this->assertSame('GET', $http->lastRequest['method']);
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9/documentBase64', $http->lastRequest['url']);
        $this->assertSame('BASE64PAYLOAD', $payload);
    }

    /** @test */
    public function submit_and_accept_post_the_signature_to_sign()
    {
        $http = (new FakeHttpClient())->queue(['doc_status' => 1])->queue(['doc_status' => 1]);
        $driver = $this->driver($http);

        $driver->submit('doc-9', 'PKCS7BLOB');
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9/sign', $http->requests[0]['url']);
        $this->assertSame('PKCS7BLOB', $http->requests[0]['payload']['signature']);

        $driver->accept('doc-9', 'PKCS7BLOB');
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9/sign', $http->requests[1]['url']);
        $this->assertSame('PKCS7BLOB', $http->requests[1]['payload']['signature']);
    }

    /** @test */
    public function a_non_2xx_on_a_get_throws_and_an_empty_body_5xx_uses_the_fallback_message()
    {
        // GET error path, with the operator message under `error_msg`
        try {
            $this->driver((new FakeHttpClient())->queue(['error_msg' => 'not found'], 404))->status('missing');
            $this->fail('Expected EinvoiceException.');
        } catch (EinvoiceException $e) {
            $this->assertSame('not found', $e->getMessage());
            $this->assertSame(404, $e->getCode());
        }

        // empty-body 5xx -> the "Didox HTTP 500." fallback
        try {
            $this->driver((new FakeHttpClient())->queue([], 500))->list();
            $this->fail('Expected EinvoiceException.');
        } catch (EinvoiceException $e) {
            $this->assertSame('Didox HTTP 500.', $e->getMessage());
        }
    }

    /** @test */
    public function reject_cancel_and_delete_draft_hit_their_paths()
    {
        $http = (new FakeHttpClient())->queue(['doc_status' => 3])->queue(['doc_status' => 3])->queue([]);
        $driver = $this->driver($http);

        $driver->reject('doc-9', 'BLOB', 'wrong items');
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9/reject', $http->requests[0]['url']);
        $this->assertSame('wrong items', $http->requests[0]['payload']['comment']);
        $this->assertSame('BLOB', $http->requests[0]['payload']['signature']);

        $driver->cancel('doc-9', 'BLOB');
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9/delete', $http->requests[1]['url']);

        $driver->deleteDraft('doc-9');
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9/delete/draft', $http->requests[2]['url']);
    }

    /** @test */
    public function list_builds_a_query_string_with_a_get()
    {
        $http = (new FakeHttpClient())->queue(['documents' => []]);

        $this->driver($http)->list(['owner' => 1, 'status' => 60]);

        $this->assertSame('GET', $http->lastRequest['method']);
        $this->assertStringStartsWith('https://testapi3.didox.uz/v2/documents?', $http->lastRequest['url']);
        $this->assertStringContainsString('owner=1', $http->lastRequest['url']);
    }

    /** @test */
    public function status_reads_the_doc_status_into_a_value_object()
    {
        $http = (new FakeHttpClient())->queue(['data' => ['_id' => 'doc-9', 'doc_status' => DocumentStatus::REJECTED]]);

        $status = $this->driver($http)->status('doc-9');

        $this->assertSame('GET', $http->lastRequest['method']);
        $this->assertSame('https://testapi3.didox.uz/v1/documents/doc-9', $http->lastRequest['url']);
        $this->assertSame(DocumentStatus::REJECTED, $status->code());
        $this->assertTrue($status->isRejected());
    }

    /** @test */
    public function a_non_2xx_throws_an_einvoice_exception_with_the_operator_message()
    {
        $http = (new FakeHttpClient())->queue(['message' => 'Invalid TIN'], 400);

        try {
            $this->driver($http)->createDocument($this->document());
            $this->fail('Expected EinvoiceException.');
        } catch (EinvoiceException $e) {
            $this->assertSame('Invalid TIN', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }
    }

    /** @test */
    public function missing_partner_token_throws()
    {
        $this->expectException(EinvoiceException::class);
        (new DidoxDriver(['partner_token' => '', 'user_key' => 'UK'], new FakeHttpClient()))->status('doc-1');
    }

    /** @test */
    public function a_business_call_without_a_session_throws()
    {
        // partner token present, but no user_key and no login()
        $this->expectException(EinvoiceException::class);
        (new DidoxDriver(['partner_token' => 'PT'], new FakeHttpClient()))->status('doc-1');
    }

    /** @test */
    public function a_transport_fault_propagates()
    {
        $this->expectException(TransportException::class);
        (new DidoxDriver(['partner_token' => 'PT', 'user_key' => 'UK'], new ThrowingHttpClient()))->status('doc-1');
    }
}
