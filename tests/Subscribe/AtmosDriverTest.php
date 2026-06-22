<?php

namespace Goodoneuz\PayUz\Tests\Subscribe;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Subscribe\Charge;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Subscribe\Drivers\AtmosDriver;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException;
use Goodoneuz\PayUz\Subscribe\Exceptions\OperationException;

/**
 * ATMOS driver (Subscribe model): the OAuth token flow, tiyin pass-through (no
 * conversion), the OK envelope, card bind/confirm, create/pre-apply/apply, and the
 * callback signature — all against a fake transport, no network.
 */
class AtmosDriverTest extends TestCase
{
    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        $config = array_merge([
            'consumer_key'    => 'ck',
            'consumer_secret' => 'cs',
            'store_id'        => 'store-1',
            'api_key'         => 'apikey',
            'lang'            => 'uz',
            'test'            => true,
        ], $overrides);

        return new AtmosDriver($config, $http);
    }

    /** Queue an OAuth token response (consumed by the first call). */
    private function withToken(FakeHttpClient $http)
    {
        return $http->queue(['access_token' => 'BEARER123', 'expires_in' => 3600]);
    }

    /** @test */
    public function it_authenticates_with_oauth_then_calls_merchant_with_a_bearer()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK'], 'binding_id' => 555]);

        $card = $this->driver($http)->createCard('8600123412340000', '0399', true, ['phone' => '+998901234567']);

        // token call: form-encoded POST to the test host with Basic auth
        $token = $http->requests[0];
        $this->assertSame('https://test-partner.atmos.uz/token', $token['url']);
        $this->assertTrue($token['form']);
        $this->assertSame('client_credentials', $token['payload']['grant_type']);
        $this->assertSame('Basic '.base64_encode('ck:cs'), $token['headers']['Authorization']);

        // merchant call: JSON POST with the bearer + store_id in the body
        $bind = $http->requests[1];
        $this->assertSame('https://test-partner.atmos.uz/merchant/card/bind', $bind['url']);
        $this->assertSame('Bearer BEARER123', $bind['headers']['Authorization']);
        $this->assertSame('store-1', $bind['payload']['store_id']);
        $this->assertSame('8600123412340000', $bind['payload']['card_number']);
        $this->assertSame('9903', $bind['payload']['expiry']);  // "MMYY" 0399 -> "YYmm" 9903
        $this->assertSame('+998901234567', $bind['payload']['phone']);

        // createCard returns the interim binding_id as the (unverified) token
        $this->assertSame('555', $card->token());
        $this->assertFalse($card->isVerified());
    }

    /** @test */
    public function the_bearer_token_is_cached_across_calls()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)
            ->queue(['result' => ['code' => 'OK'], 'transaction_id' => 10, 'store_transaction' => ['confirmed' => false, 'amount' => 5000000]])
            ->queue(['result' => ['code' => 'OK'], 'store_transaction' => ['confirmed' => true, 'amount' => 5000000]]);
        $driver = $this->driver($http);

        $driver->createReceipt(5000000, ['account' => 'order-1']);
        $driver->getReceipt(10);

        $tokenCalls = array_filter($http->requests, function ($r) {
            return substr($r['url'], -6) === '/token';
        });
        $this->assertCount(1, $tokenCalls); // fetched once, then cached
    }

    /** @test */
    public function create_receipt_passes_tiyin_through_without_conversion()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK'], 'transaction_id' => 10, 'store_transaction' => ['confirmed' => false, 'amount' => 5000000]]);

        $charge = $this->driver($http)->createReceipt(5000000, ['account' => 'order-1']);

        $create = $http->requests[1];
        $this->assertSame('https://test-partner.atmos.uz/merchant/pay/create', $create['url']);
        $this->assertSame(5000000, $create['payload']['amount']); // tiyin, NOT /100
        $this->assertSame('order-1', $create['payload']['account']);
        $this->assertSame('uz', $create['payload']['lang']);
        $this->assertSame('10', $charge->id());
        $this->assertSame(Charge::STATE_CREATED, $charge->state());
    }

    /** @test */
    public function verify_card_confirms_the_binding_and_returns_the_card_token()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK'], 'card_token' => 'CARDTOK', 'masked_pan' => '8600****0000']);

        $card = $this->driver($http)->verifyCard('555', '111111');

        $confirm = $http->requests[1];
        $this->assertSame('https://test-partner.atmos.uz/merchant/card/bind/confirm', $confirm['url']);
        $this->assertSame(555, $confirm['payload']['binding_id']); // numeric id -> int
        $this->assertSame('111111', $confirm['payload']['otp']);
        $this->assertSame('CARDTOK', $card->token());
        $this->assertTrue($card->isVerified());
    }

    /** @test */
    public function pay_receipt_pre_applies_the_token_then_applies_the_fixed_otp()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)
            ->queue(['result' => ['code' => 'OK']])                                                    // pre-apply
            ->queue(['result' => ['code' => 'OK'], 'store_transaction' => ['confirmed' => true, 'amount' => 5000000]]); // apply

        $charge = $this->driver($http)->payReceipt(10, 'CARDTOK');

        $pre = $http->requests[1];
        $this->assertSame('https://test-partner.atmos.uz/merchant/pay/pre-apply', $pre['url']);
        $this->assertSame(10, $pre['payload']['transaction_id']);
        $this->assertSame('CARDTOK', $pre['payload']['card_token']);

        $apply = $http->requests[2];
        $this->assertSame('https://test-partner.atmos.uz/merchant/pay/apply', $apply['url']);
        $this->assertSame('111111', $apply['payload']['otp']); // fixed OTP for token charges

        $this->assertTrue($charge->isPaid());
        $this->assertSame(5000000, $charge->amount());
    }

    /** @test */
    public function a_hold_request_is_rejected_because_atmos_hold_is_unverified()
    {
        $http = new FakeHttpClient();
        $this->withToken($http); // token may be fetched before the guard

        $this->expectException(OperationException::class);
        $this->driver($http)->payReceipt(10, 'tok', ['hold' => true]);
    }

    /** @test */
    public function confirm_hold_is_not_supported()
    {
        $this->expectException(OperationException::class);
        $this->driver(new FakeHttpClient())->confirmHold(10);
    }

    /** @test */
    public function cancel_and_status_map_to_cancel_and_info()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)
            ->queue(['result' => ['code' => 'OK']])                                                       // cancel
            ->queue(['result' => ['code' => 'OK'], 'store_transaction' => ['confirmed' => true, 'amount' => 5000000]]); // info
        $driver = $this->driver($http);

        $cancelled = $driver->cancelReceipt(10);
        $this->assertSame('https://test-partner.atmos.uz/merchant/pay/cancel', $http->requests[1]['url']);
        $this->assertTrue($cancelled->isCancelled());

        $this->assertSame(Charge::STATE_PAID, $driver->checkReceipt(10)); // info -> confirmed -> paid
    }

    /** @test */
    public function create_card_requires_a_phone()
    {
        $this->expectException(SubscribeException::class);
        $this->driver(new FakeHttpClient())->createCard('8600000000000000', '0399', true, []);
    }

    /** @test */
    public function a_non_ok_result_code_throws_with_the_description()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'STORE_NOT_FOUND', 'description' => 'Store not found']]);

        try {
            $this->driver($http)->createReceipt(1000, ['account' => 'o']);
            $this->fail('Expected SubscribeException.');
        } catch (SubscribeException $e) {
            $this->assertSame('Store not found', $e->getMessage());
        }
    }

    /** @test */
    public function a_token_response_without_an_access_token_throws()
    {
        $http = (new FakeHttpClient())->queue(['error' => 'invalid_client']); // no access_token

        $this->expectException(SubscribeException::class);
        $this->driver($http)->createReceipt(1000, ['account' => 'o']);
    }

    /** @test */
    public function missing_store_id_throws_before_any_merchant_call()
    {
        $http = new FakeHttpClient();
        $this->withToken($http); // token fetch may happen first

        $this->expectException(SubscribeException::class);
        $this->driver($http, ['store_id' => ''])->createReceipt(1000, ['account' => 'o']);
    }

    /** @test */
    public function callback_signature_matches_the_documented_md5_and_rejects_tampering()
    {
        $driver = $this->driver(new FakeHttpClient());

        $payload = ['store_id' => 'store-1', 'transaction_id' => '10', 'invoice' => 'inv-1', 'amount' => '5000000'];
        $payload['sign'] = md5('store-1'.'10'.'inv-1'.'5000000'.'apikey');

        $this->assertTrue($driver->verifyCallback($payload));

        $payload['amount'] = '1'; // tamper the amount -> signature no longer matches
        $this->assertFalse($driver->verifyCallback($payload));

        $charge = $driver->parseCallback(['transaction_id' => '10', 'amount' => '5000000']);
        $this->assertSame('10', $charge->id());
        $this->assertSame(5000000, $charge->amount());
    }

    /** @test */
    public function callback_is_rejected_without_a_configured_api_key()
    {
        $driver = $this->driver(new FakeHttpClient(), ['api_key' => null]);

        $this->assertFalse($driver->verifyCallback(['transaction_id' => '10', 'sign' => 'x']));
    }

    /** @test */
    public function the_bearer_token_is_refetched_once_it_expires()
    {
        $http = new FakeHttpClient();
        $http->queue(['access_token' => 'T1', 'expires_in' => 120]) // expiry = now + max(60, 60) = now+60
            ->queue(['result' => ['code' => 'OK'], 'transaction_id' => 1, 'store_transaction' => ['confirmed' => false]])
            ->queue(['access_token' => 'T2', 'expires_in' => 120])  // refresh
            ->queue(['result' => ['code' => 'OK'], 'transaction_id' => 2, 'store_transaction' => ['confirmed' => false]]);

        $config = ['consumer_key' => 'ck', 'consumer_secret' => 'cs', 'store_id' => 's1', 'test' => true];
        $driver = new class($config, $http) extends AtmosDriver {
            public $clock = 1000;
            protected function now() { return $this->clock; }
        };

        $driver->createReceipt(1000, ['account' => 'o']);   // fetch T1, expires at 1060
        $driver->clock = 5000;                               // past expiry
        $driver->createReceipt(1000, ['account' => 'o']);   // must refetch -> T2

        $tokenCalls = array_filter($http->requests, function ($r) {
            return substr($r['url'], -6) === '/token';
        });
        $this->assertCount(2, $tokenCalls);
        $this->assertSame('Bearer T1', $http->requests[1]['headers']['Authorization']);
        $this->assertSame('Bearer T2', $http->requests[3]['headers']['Authorization']);
    }

    /** @test */
    public function check_card_finds_the_token_in_the_card_list()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK'], 'cards' => [
            ['card_id' => 'OTHER', 'card_number' => '8601****1111'],
            ['token' => 'CARDTOK', 'masked_pan' => '8600****0000', 'status' => true],
        ]]);

        $card = $this->driver($http)->checkCard('CARDTOK');

        $this->assertSame('https://test-partner.atmos.uz/merchant/card/list', $http->requests[1]['url']);
        $this->assertSame('CARDTOK', $card->token());
        $this->assertSame('8600****0000', $card->number());
        $this->assertTrue($card->isVerified());
    }

    /** @test */
    public function check_card_throws_when_the_token_is_absent()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK'], 'cards' => []]);

        $this->expectException(SubscribeException::class);
        $this->driver($http)->checkCard('MISSING');
    }

    /** @test */
    public function remove_card_unbinds_and_returns_true()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK']]);

        $ok = $this->driver($http)->removeCard('CARDTOK');

        $this->assertSame('https://test-partner.atmos.uz/merchant/card/unbind', $http->requests[1]['url']);
        $this->assertSame('CARDTOK', $http->requests[1]['payload']['card_token']);
        $this->assertSame('store-1', $http->requests[1]['payload']['store_id']);
        $this->assertTrue($ok);
    }

    /** @test */
    public function a_transport_fault_propagates_unconverted()
    {
        $driver = new AtmosDriver(['consumer_key' => 'ck', 'consumer_secret' => 'cs', 'store_id' => 's1'], new ThrowingHttpClient());

        // fails on the OAuth postForm; must surface as TransportException, not SubscribeException
        $this->expectException(TransportException::class);
        $driver->createReceipt(1000, ['account' => 'o']);
    }

    /** @test */
    public function pay_receipt_honours_an_explicit_otp_override()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)
            ->queue(['result' => ['code' => 'OK']])
            ->queue(['result' => ['code' => 'OK'], 'store_transaction' => ['confirmed' => true]]);

        $this->driver($http)->payReceipt(10, 'CARDTOK', ['otp' => '654321']);

        $this->assertSame('654321', $http->requests[2]['payload']['otp']); // overrides the fixed 111111
    }

    /** @test */
    public function send_verify_code_is_a_no_op_that_reports_sent()
    {
        $http = new FakeHttpClient(); // nothing queued — must make no request
        $vc = $this->driver($http)->sendVerifyCode('555');

        $this->assertTrue($vc->wasSent());
        $this->assertCount(0, $http->requests);
    }

    /** @test */
    public function expiry_transpose_strips_separators_and_passes_malformed_input_through()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)
            ->queue(['result' => ['code' => 'OK'], 'binding_id' => 1])
            ->queue(['access_token' => 'T2', 'expires_in' => 3600]); // (unused) keep a buffer

        $this->driver($http)->createCard('8600000000000000', '03/99', true, ['phone' => '+998901111111']);
        $this->assertSame('9903', $http->requests[1]['payload']['expiry']); // "03/99" -> digits 0399 -> 9903
    }

    /** @test */
    public function create_receipt_forwards_the_description_to_atmos_details()
    {
        $http = new FakeHttpClient();
        $this->withToken($http)->queue(['result' => ['code' => 'OK'], 'transaction_id' => 1, 'store_transaction' => ['confirmed' => false]]);

        $this->driver($http)->createReceipt(1000, ['account' => 'o'], ['description' => 'Order #1']);

        $this->assertSame('Order #1', $http->requests[1]['payload']['details']);
    }
}
