<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Checkout\Drivers\MulticardDriver;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;

/**
 * Multicard driver: token auth + 401 retry, the real HTTP verbs (POST/PUT/DELETE/
 * GET), tiyin pass-through (no conversion), the {success} envelope, and the two
 * webhook signature schemes — all against a fake transport, no network.
 */
class MulticardDriverTest extends TestCase
{
    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        $config = array_merge([
            'base_url'        => 'https://dev-mesh.multicard.uz',
            'application_id'  => 'app',
            'secret'          => 'sec',
            'store_id'        => 42,
            'callback_url'    => 'https://app/cb',
            'language'        => 'uz',
            'callback_scheme' => 'webhooks',
        ], $overrides);

        return new MulticardDriver($config, $http);
    }

    private function withToken(FakeHttpClient $http, $expiry = '2099-12-31 23:59:59')
    {
        return $http->queue(['token' => 'TOK', 'role' => 'merchant', 'expiry' => $expiry]);
    }

    /** @test */
    public function it_authenticates_then_creates_an_invoice_in_tiyin_with_both_auth_headers()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['success' => true, 'data' => [
            'uuid' => 'U-1', 'invoice_id' => 'order-1', 'amount' => 100000, 'checkout_url' => 'https://app.rhmt.uz/U-1', 'status' => 'draft',
        ]]);

        $result = $this->driver($http)->createPayment(
            Payment::make(100000, 'order-1')->returnTo('https://app/r')->notifyAt('https://app/cb')
        );

        // auth call
        $auth = $http->requests[0];
        $this->assertSame('POST', $auth['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/auth', $auth['url']);
        $this->assertSame(['application_id' => 'app', 'secret' => 'sec'], $auth['payload']);

        // invoice call
        $inv = $http->requests[1];
        $this->assertSame('POST', $inv['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/payment/invoice', $inv['url']);
        $this->assertSame('Bearer TOK', $inv['headers']['Authorization']);
        $this->assertSame('TOK', $inv['headers']['X-Access-Token']);
        $this->assertSame(42, $inv['payload']['store_id']);
        $this->assertSame(100000, $inv['payload']['amount']);  // tiyin, NOT /100
        $this->assertSame('order-1', $inv['payload']['invoice_id']);
        $this->assertSame('https://app/r', $inv['payload']['return_url']);

        $this->assertSame(PaymentResult::STATUS_CREATED, $result->status());
        $this->assertSame('U-1', $result->paymentId());
        $this->assertSame('order-1', $result->orderId());
        $this->assertSame('https://app.rhmt.uz/U-1', $result->payUrl());
        $this->assertSame(100000, $result->amount());          // tiyin back
    }

    /** @test */
    public function extras_pass_through_but_never_override_canonical_keys()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'draft']]);

        $ofd = [['qty' => 1, 'price' => 100000, 'total' => 100000, 'mxik' => '00702001001000001', 'package_code' => '1', 'name' => 'Plan']];
        $this->driver($http)->createPayment(
            Payment::make(100000, 'order-1')->with(['ofd' => $ofd, 'amount' => 999, 'ttl' => 600])
        );

        $body = $http->requests[1]['payload'];
        $this->assertSame(100000, $body['amount']);   // canonical wins over the stray extra
        $this->assertSame($ofd, $body['ofd']);        // additive extra passes through
        $this->assertSame(600, $body['ttl']);
    }

    /** @test */
    public function charge_token_posts_to_payment_with_the_card_token()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['success' => true, 'data' => [
            'uuid' => 'U-2', 'status' => 'success', 'card_token' => 'CT', 'card_pan' => '8600****0000', 'payment_amount' => 100000,
        ]]);

        $result = $this->driver($http)->chargeToken('CT', Payment::make(100000, 'order-2'));

        $this->assertSame('POST', $http->requests[1]['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/payment', $http->requests[1]['url']);
        $this->assertSame(['token' => 'CT'], $http->requests[1]['payload']['card']);
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('CT', $result->cardToken());
        $this->assertSame('8600****0000', $result->maskedCard());
        $this->assertSame(100000, $result->amount());
    }

    /** @test */
    public function capture_puts_to_the_hold_charge_endpoint()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'success']]);

        $result = $this->driver($http)->capture('U1', 5000);

        $this->assertSame('PUT', $http->requests[1]['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/payment/hold/U1/charge', $http->requests[1]['url']);
        $this->assertSame(5000, $http->requests[1]['payload']['amount']);
        $this->assertTrue($result->isSuccessful());
    }

    /** @test */
    public function capture_without_an_amount_throws()
    {
        $this->expectException(CheckoutException::class);
        $this->driver(new FakeHttpClient())->capture('U1', null);
    }

    /** @test */
    public function full_refund_deletes_the_payment_and_partial_refund_hits_the_partial_endpoint()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'total_amount' => 5000, 'status' => 'revert']])
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'revert']]);
        $driver = $this->driver($http);

        $full = $driver->refund('U1');
        $this->assertSame('DELETE', $http->requests[1]['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/payment/U1', $http->requests[1]['url']);
        $this->assertNull($http->requests[1]['payload']); // no body
        $this->assertTrue($full->isRefunded());
        $this->assertSame(5000, $full->amount());          // from total_amount

        $partial = $driver->refund('U1', 2000);
        $this->assertSame('DELETE', $http->requests[2]['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/payment/U1/partial', $http->requests[2]['url']);
        $this->assertSame(2000, $http->requests[2]['payload']['refund_amount']);
        $this->assertTrue($partial->isRefunded());
        $this->assertSame(2000, $partial->amount());
    }

    /** @test */
    public function a_refund_result_never_carries_the_card_pan()
    {
        $http = new FakeHttpClient();
        // gateway echoes a card_pan, but a refund result must not surface/persist it
        $this->withToken($http)->queue(['success' => true, 'data' => ['uuid' => 'U1', 'card_pan' => '8600****0000', 'status' => 'revert']]);

        $result = $this->driver($http)->refund('U1', 2000);

        $this->assertNull($result->maskedCard());
        $this->assertArrayNotHasKey('masked_card', array_filter($result->toArray(), function ($v) {
            return $v !== null;
        }));
    }

    /** @test */
    public function status_gets_the_payment_by_uuid()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'hold']]);

        $result = $this->driver($http)->status('U1');

        $this->assertSame('GET', $http->requests[1]['method']);
        $this->assertSame('https://dev-mesh.multicard.uz/payment/U1', $http->requests[1]['url']);
        $this->assertNull($http->requests[1]['payload']); // bodyless GET
        $this->assertTrue($result->isHeld());
    }

    /** @test */
    public function a_success_false_envelope_throws_with_the_error_details()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['success' => false, 'error' => ['code' => 'ERROR_FIELDS', 'details' => 'Invalid fields']]);

        try {
            $this->driver($http)->createPayment(Payment::make(1000, 'o'));
            $this->fail('Expected CheckoutException.');
        } catch (CheckoutException $e) {
            $this->assertSame('Invalid fields', $e->getMessage());
        }
    }

    /** @test */
    public function a_401_drops_the_token_and_retries_once()
    {
        $http = new FakeHttpClient();
        $http->queue(['token' => 'T1', 'expiry' => '2099-12-31 23:59:59'])   // auth #1
            ->queue(['error' => ['code' => 'UNAUTHORIZED']], 401)            // invoice -> 401
            ->queue(['token' => 'T2', 'expiry' => '2099-12-31 23:59:59'])    // auth #2 (refresh)
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'draft']]); // retry -> ok

        $result = $this->driver($http)->createPayment(Payment::make(1000, 'o'));

        $this->assertCount(4, $http->requests);
        $this->assertSame('Bearer T2', $http->requests[3]['headers']['Authorization']); // retried with the fresh token
        $this->assertSame('U1', $result->paymentId());
    }

    /** @test */
    public function a_repeated_401_exhausts_the_single_retry_and_throws()
    {
        $http = new FakeHttpClient();
        $http->queue(['token' => 'T1', 'expiry' => '2099-12-31 23:59:59'])  // auth #1
            ->queue(['error' => ['code' => 'UNAUTHORIZED']], 401)           // invoice -> 401
            ->queue(['token' => 'T2', 'expiry' => '2099-12-31 23:59:59'])   // auth #2 (refresh)
            ->queue(['error' => ['code' => 'UNAUTHORIZED']], 401);          // retry -> still 401

        try {
            $this->driver($http)->createPayment(Payment::make(1000, 'o'));
            $this->fail('Expected CheckoutException after the single retry.');
        } catch (CheckoutException $e) {
            $this->assertCount(4, $http->requests); // exactly one retry, no loop
        }
    }

    /** @test */
    public function a_transport_fault_after_a_successful_auth_propagates()
    {
        // token succeeds, then the business call throws at the transport level
        $flaky = new class implements HttpClient {
            public function post($url, array $payload, array $headers = []) { return $this->handle($url); }
            public function postForm($url, array $fields, array $headers = []) { return $this->handle($url); }
            public function request($method, $url, $payload = null, array $headers = []) { return $this->handle($url); }
            private function handle($url)
            {
                if (substr($url, -5) === '/auth') {
                    return ['status' => 200, 'body' => ['token' => 'T', 'expiry' => '2099-12-31 23:59:59'], 'raw' => ''];
                }
                throw new TransportException('connection reset');
            }
        };

        $driver = new MulticardDriver(['application_id' => 'a', 'secret' => 's', 'store_id' => 1], $flaky);

        $this->expectException(TransportException::class);
        $driver->status('U1'); // GET faults after auth
    }

    /** @test */
    public function it_maps_statuses_from_real_api_responses()
    {
        $cases = [
            'progress' => PaymentResult::STATUS_PENDING,
            'billing'  => PaymentResult::STATUS_PENDING,
            'error'    => PaymentResult::STATUS_FAILED,
            'revert'   => PaymentResult::STATUS_REFUNDED,
            'mystery'  => PaymentResult::STATUS_PENDING,
        ];

        foreach ($cases as $multicard => $expected) {
            $http = new FakeHttpClient();
            $this->withToken($http)->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => $multicard]]);
            $result = $this->driver($http)->status('U1'); // through resultFrom(), not parseWebhook()
            $this->assertSame($expected, $result->status(), 'status='.$multicard);
        }
    }

    /** @test */
    public function the_token_is_cached_while_valid_and_refetched_once_expired()
    {
        // far-future expiry -> one /auth across two calls
        $cached = new FakeHttpClient();
        $this->withToken($cached, '2099-12-31 23:59:59')
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'draft']])
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'success']]);
        $d = $this->driver($cached);
        $d->createPayment(Payment::make(1000, 'o1'));
        $d->status('U1');
        $this->assertCount(1, $this->authCalls($cached));

        // past expiry -> refetched each call
        $expired = new FakeHttpClient();
        $this->withToken($expired, '2000-01-01 00:00:00')
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'draft']]);
        $this->withToken($expired, '2000-01-01 00:00:00')
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'success']]);
        $d2 = $this->driver($expired);
        $d2->createPayment(Payment::make(1000, 'o1'));
        $d2->status('U1');
        $this->assertCount(2, $this->authCalls($expired));
    }

    /** @test */
    public function it_maps_multicard_statuses()
    {
        $driver = $this->driver(new FakeHttpClient());
        $cases = [
            'draft'    => PaymentResult::STATUS_CREATED,
            'progress' => PaymentResult::STATUS_PENDING,
            'billing'  => PaymentResult::STATUS_PENDING,
            'hold'     => PaymentResult::STATUS_HELD,
            'success'  => PaymentResult::STATUS_SUCCEEDED,
            'error'    => PaymentResult::STATUS_FAILED,
            'revert'   => PaymentResult::STATUS_REFUNDED,
            'mystery'  => PaymentResult::STATUS_PENDING,
        ];

        foreach ($cases as $multicard => $expected) {
            $result = $driver->parseWebhook(['status' => $multicard, 'uuid' => 'U1']);
            $this->assertSame($expected, $result->status(), 'status='.$multicard);
        }
    }

    /** @test */
    public function webhook_scheme_webhooks_uses_sha1_and_rejects_tampering()
    {
        $driver = $this->driver(new FakeHttpClient()); // default scheme = webhooks

        $payload = ['uuid' => 'U1', 'invoice_id' => 'inv1', 'amount' => 200000];
        $payload['sign'] = sha1('U1'.'inv1'.'200000'.'sec');
        $this->assertTrue($driver->verifyWebhook($payload));

        $payload['amount'] = 1; // tamper
        $this->assertFalse($driver->verifyWebhook($payload));
    }

    /** @test */
    public function webhook_scheme_success_uses_md5_over_the_payload_store_id()
    {
        // config store_id (42) differs from the payload store_id (99): the signed
        // string must use the PAYLOAD value the sender hashed, not the config one.
        $driver = $this->driver(new FakeHttpClient(), ['callback_scheme' => 'success', 'store_id' => 42]);

        $payload = ['store_id' => '99', 'invoice_id' => 'inv1', 'amount' => 200000];
        $payload['sign'] = md5('99'.'inv1'.'200000'.'sec');
        $this->assertTrue($driver->verifyWebhook($payload));

        $payload['store_id'] = '42'; // tamper
        $this->assertFalse($driver->verifyWebhook($payload));
    }

    /** @test */
    public function webhook_signature_comparison_is_case_insensitive()
    {
        $driver = $this->driver(new FakeHttpClient());

        $payload = ['uuid' => 'U1', 'invoice_id' => 'inv1', 'amount' => 200000];
        $payload['sign'] = strtoupper(sha1('U1'.'inv1'.'200000'.'sec')); // uppercase hex still verifies

        $this->assertTrue($driver->verifyWebhook($payload));
    }

    /** @test */
    public function webhook_is_rejected_without_a_secret_or_sign()
    {
        $this->assertFalse($this->driver(new FakeHttpClient(), ['secret' => null])->verifyWebhook(['sign' => 'x']));
        $this->assertFalse($this->driver(new FakeHttpClient())->verifyWebhook(['uuid' => 'U1'])); // no sign
    }

    /** @test */
    public function parse_webhook_normalizes_the_payload()
    {
        $result = $this->driver(new FakeHttpClient())->parseWebhook([
            'uuid' => 'U1', 'invoice_id' => 'order-1', 'status' => 'success', 'amount' => 200000, 'card_pan' => '8600****0000',
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('U1', $result->paymentId());
        $this->assertSame('order-1', $result->orderId());
        $this->assertSame(200000, $result->amount());
        $this->assertSame('8600****0000', $result->maskedCard());
    }

    /** @test */
    public function missing_credentials_throw()
    {
        $http = new FakeHttpClient(); // nothing queued -> would throw if a call were made
        $driver = $this->driver($http, ['application_id' => '']);

        $this->expectException(CheckoutException::class);
        $driver->createPayment(Payment::make(1000, 'o'));
    }

    /** @test */
    public function a_transport_fault_propagates()
    {
        $driver = new MulticardDriver(['application_id' => 'a', 'secret' => 's', 'store_id' => 1], new ThrowingHttpClient());

        $this->expectException(TransportException::class);
        $driver->createPayment(Payment::make(1000, 'o'));
    }

    /**
     * @param FakeHttpClient $http
     * @return array
     */
    private function authCalls(FakeHttpClient $http)
    {
        return array_filter($http->requests, function ($r) {
            return substr($r['url'], -5) === '/auth';
        });
    }
}
