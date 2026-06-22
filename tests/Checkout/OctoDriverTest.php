<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Checkout\Drivers\OctoDriver;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;

/**
 * Octo driver: som-conversion, request/response mapping, the error envelope, and
 * webhook verification — all against a fake transport, no network.
 */
class OctoDriverTest extends TestCase
{
    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        $config = array_merge([
            'shop_id'    => 42,
            'secret'     => 'sec',
            'unique_key' => 'uk',
            'test'       => true,
            'return_url' => 'https://app/return',
            'notify_url' => 'https://app/webhook',
            'language'   => 'uz',
        ], $overrides);

        return new OctoDriver($config, $http);
    }

    /** @test */
    public function create_payment_posts_prepare_with_som_amount_and_parses_the_pay_url()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => [
            'shop_transaction_id' => 'order-1',
            'octo_payment_UUID'   => 'U-100',
            'status'              => 'created',
            'octo_pay_url'        => 'https://secure.octo.uz/pay/U-100',
            'total_sum'           => 12000.0,
        ]]);

        $result = $this->driver($http)->createPayment(Payment::make(1200000, 'order-1')->describedAs('Pro'));

        $this->assertSame('https://secure.octo.uz/prepare_payment', $http->lastRequest['url']);
        $body = $http->lastRequest['payload'];
        $this->assertSame(42, $body['octo_shop_id']);          // int
        $this->assertSame('sec', $body['octo_secret']);
        $this->assertSame('order-1', $body['shop_transaction_id']);
        $this->assertTrue($body['auto_capture']);
        $this->assertTrue($body['test']);
        $this->assertSame(12000.0, $body['total_sum']);        // 1_200_000 tiyin -> 12000.00 som
        $this->assertSame('https://app/return', $body['return_url']); // config fallback
        $this->assertSame('uz', $body['language']);

        $this->assertSame(PaymentResult::STATUS_CREATED, $result->status());
        $this->assertSame('U-100', $result->paymentId());
        $this->assertSame('https://secure.octo.uz/pay/U-100', $result->payUrl());
        $this->assertSame(1200000, $result->amount());         // som -> tiyin on the way back
    }

    /** @test */
    public function authorize_only_sends_auto_capture_false_and_extras_do_not_override_canonical()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1', 'status' => 'created']]);

        $payment = Payment::make(500000, 'order-2')->authorizeOnly()
            ->with(['auto_capture' => true, 'language' => 'en', 'basket' => [['position_desc' => 'x', 'count' => 1, 'price' => 5000.0]]]);
        $this->driver($http)->createPayment($payment);

        $body = $http->lastRequest['payload'];
        $this->assertFalse($body['auto_capture']);             // canonical wins over the stray extra
        $this->assertSame('uz', $body['language']);            // canonical wins
        $this->assertSame([['position_desc' => 'x', 'count' => 1, 'price' => 5000.0]], $body['basket']); // additive extra passes through
    }

    /** @test */
    public function charge_token_prepares_then_pays_the_uuid_with_the_token()
    {
        $http = (new FakeHttpClient())
            ->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U-7', 'status' => 'created']])      // prepare
            // the /pay response is camelCase (data.uuid/status/redirectUrl/totalSum)
            ->queue(['error' => 0, 'data' => ['uuid' => 'U-7', 'status' => 'succeeded', 'redirectUrl' => 'https://x/3ds', 'totalSum' => 12000.0]]);

        $result = $this->driver($http)->chargeToken('card_tok', Payment::make(1200000, 'order-7'));

        $this->assertSame('https://secure.octo.uz/prepare_payment', $http->requests[0]['url']);
        $this->assertSame('https://secure.octo.uz/pay/U-7', $http->requests[1]['url']);
        $this->assertSame('card_tok', $http->requests[1]['payload']['card_token']);
        $this->assertSame('bank_card', $http->requests[1]['payload']['method']); // default
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('https://x/3ds', $result->payUrl()); // camelCase redirectUrl parsed
        $this->assertSame(1200000, $result->amount());         // camelCase totalSum -> tiyin
    }

    /** @test */
    public function it_converts_fractional_som_amounts_both_ways()
    {
        $http = (new FakeHttpClient())
            ->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1', 'status' => 'created', 'total_sum' => 2.30]])
            ->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1', 'status' => 'capture']]);
        $driver = $this->driver($http);

        // 230 tiyin -> 2.30 som on the wire; 2.30 som back -> 230 tiyin.
        $created = $driver->createPayment(Payment::make(230, 'order-f'));
        $this->assertEqualsWithDelta(2.30, $http->requests[0]['payload']['total_sum'], 0.0001);
        $this->assertSame(230, $created->amount());

        // 123_455 tiyin -> 1234.55 som (round() is load-bearing here).
        $driver->capture('U1', 123455);
        $this->assertEqualsWithDelta(1234.55, $http->requests[1]['payload']['final_amount'], 0.0001);
    }

    /** @test */
    public function create_payment_carries_the_order_id_for_status_calls()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => [
            'shop_transaction_id' => 'order-1', 'octo_payment_UUID' => 'U1', 'status' => 'created',
        ]]);

        $result = $this->driver($http)->createPayment(Payment::make(1000, 'order-1'));

        $this->assertSame('order-1', $result->orderId());  // status() id
        $this->assertSame('U1', $result->paymentId());      // capture/refund id
    }

    /** @test */
    public function it_maps_octo_statuses_to_normalized_statuses()
    {
        $cases = [
            ['failed', 'isFailed'],
            ['cancelled', 'isFailed'],
            ['cancel', 'isFailed'],
            ['waiting_for_capture', 'isHeld'],
            ['succeeded', 'isSuccessful'],
            ['refunded', 'isRefunded'],
            ['wait_user_action', null],   // -> PENDING
            ['brand_new_status', null],   // unknown -> PENDING
        ];

        foreach ($cases as $case) {
            list($octo, $check) = $case;
            $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['status' => $octo, 'shop_transaction_id' => 'o']]);
            $result = $this->driver($http)->status('o');

            if ($check === null) {
                $this->assertSame(PaymentResult::STATUS_PENDING, $result->status(), 'octo='.$octo);
            } else {
                $this->assertTrue($result->$check(), 'octo='.$octo.' -> '.$check);
            }
        }
    }

    /** @test */
    public function the_error_message_falls_back_to_the_deprecated_field_then_a_default()
    {
        $legacy = (new FakeHttpClient())->queue(['error' => 5, 'errorMessage' => 'legacy message']);
        try {
            $this->driver($legacy)->status('o');
            $this->fail('Expected CheckoutException.');
        } catch (CheckoutException $e) {
            $this->assertSame('legacy message', $e->getMessage());
            $this->assertSame(5, $e->getCode());
        }

        $bare = (new FakeHttpClient())->queue(['error' => 7]);
        try {
            $this->driver($bare)->status('o');
            $this->fail('Expected CheckoutException.');
        } catch (CheckoutException $e) {
            $this->assertSame('Octo request failed.', $e->getMessage());
        }
    }

    /** @test */
    public function capture_posts_set_accept_with_the_uuid_and_som_amount()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U-9', 'status' => 'capture']]);

        $result = $this->driver($http)->capture('U-9', 1200000);

        $this->assertSame('https://secure.octo.uz/set_accept', $http->lastRequest['url']);
        $body = $http->lastRequest['payload'];
        $this->assertSame('U-9', $body['octo_payment_UUID']);
        $this->assertSame('capture', $body['accept_status']);
        $this->assertSame(12000.0, $body['final_amount']);
        $this->assertTrue($result->isSuccessful());
    }

    /** @test */
    public function capture_without_an_amount_throws_because_octo_requires_final_amount()
    {
        $this->expectException(CheckoutException::class);
        $this->driver(new FakeHttpClient())->capture('U-9', null);
    }

    /** @test */
    public function refund_posts_refund_with_a_unique_refund_id_and_returns_refunded()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U-9', 'status' => 'cancelled']]);

        $result = $this->driver($http)->refund('U-9', 600000);

        $this->assertSame('https://secure.octo.uz/refund', $http->lastRequest['url']);
        $body = $http->lastRequest['payload'];
        $this->assertSame('U-9', $body['octo_payment_UUID']);
        $this->assertSame(6000.0, $body['amount']);
        $this->assertStringStartsWith('U-9-r-', $body['shop_refund_id']); // unique merchant refund id
        // a successful /refund means REFUNDED even though the echoed status was "cancelled"
        $this->assertTrue($result->isRefunded());
    }

    /** @test */
    public function refund_without_an_amount_throws()
    {
        $this->expectException(CheckoutException::class);
        $this->driver(new FakeHttpClient())->refund('U-9', null);
    }

    /** @test */
    public function status_is_keyed_on_the_shop_transaction_id()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => [
            'shop_transaction_id' => 'order-1', 'octo_payment_UUID' => 'U1', 'status' => 'waiting_for_capture',
        ]]);

        $result = $this->driver($http)->status('order-1');

        $this->assertSame('https://secure.octo.uz/check_status', $http->lastRequest['url']);
        $this->assertSame('order-1', $http->lastRequest['payload']['shop_transaction_id']);
        $this->assertArrayNotHasKey('octo_payment_UUID', $http->lastRequest['payload']);
        $this->assertTrue($result->isHeld()); // waiting_for_capture -> held
    }

    /** @test */
    public function a_nonzero_error_field_throws_even_on_http_200()
    {
        // Octo always returns HTTP 200; failure is signalled by error != 0.
        $http = (new FakeHttpClient())->queue(['error' => 2, 'errMessage' => 'Wrong secret', 'data' => null], 200);

        try {
            $this->driver($http)->createPayment(Payment::make(1000, 'o'));
            $this->fail('Expected CheckoutException.');
        } catch (CheckoutException $e) {
            $this->assertSame(2, $e->getCode());
            $this->assertSame('Wrong secret', $e->getMessage());
        }
    }

    /** @test */
    public function a_non_2xx_http_status_throws()
    {
        $http = (new FakeHttpClient())->queue([], 502);

        $this->expectException(CheckoutException::class);
        $this->driver($http)->status('order-1');
    }

    /** @test */
    public function a_transport_fault_propagates()
    {
        $this->expectException(TransportException::class);
        $this->driver(new FakeHttpClient()); // unused
        (new OctoDriver(['shop_id' => 1, 'secret' => 's'], new ThrowingHttpClient()))->status('order-1');
    }

    /** @test */
    public function missing_credentials_throw()
    {
        $this->expectException(CheckoutException::class);
        (new OctoDriver(['shop_id' => '', 'secret' => ''], new FakeHttpClient()))->createPayment(Payment::make(1000, 'o'));
    }

    /** @test */
    public function webhook_verification_matches_the_documented_sha1_and_rejects_tampering()
    {
        $driver = $this->driver(new FakeHttpClient());

        $payload = ['octo_payment_UUID' => 'U1', 'status' => 'succeeded'];
        $payload['signature'] = sha1('uk'.'U1'.'succeeded'); // unique_key . uuid . status

        $this->assertTrue($driver->verifyWebhook($payload));

        $payload['signature'] = 'tampered';
        $this->assertFalse($driver->verifyWebhook($payload));
    }

    /** @test */
    public function webhook_is_rejected_without_a_configured_unique_key()
    {
        $driver = $this->driver(new FakeHttpClient(), ['unique_key' => null]);

        $this->assertFalse($driver->verifyWebhook(['octo_payment_UUID' => 'U1', 'status' => 'succeeded', 'signature' => 'x']));
    }

    /** @test */
    public function parse_webhook_normalizes_status_and_converts_amount_to_tiyin()
    {
        $result = $this->driver(new FakeHttpClient())->parseWebhook([
            'octo_payment_UUID' => 'U1',
            'status'            => 'succeeded',
            'total_sum'         => 12000.0,
            'maskedPan'         => '860006******6311',
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('U1', $result->paymentId());
        $this->assertSame(1200000, $result->amount());        // som -> tiyin
        $this->assertSame('860006******6311', $result->maskedCard());
    }
}
