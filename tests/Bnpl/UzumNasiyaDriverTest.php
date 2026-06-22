<?php

namespace Goodoneuz\PayUz\Tests\Bnpl;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Bnpl\Drivers\UzumNasiyaDriver;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Bnpl\Exceptions\BnplException;

/**
 * Uzum Nasiya BNPL driver: the installment flow, the load-bearing tiyin<->som
 * conversion, the two-id handling, and the confirm/cancel response-code logic —
 * all against a fake transport, no network.
 */
class UzumNasiyaDriverTest extends TestCase
{
    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        return new UzumNasiyaDriver(array_merge(['token' => 'JWT'], $overrides), $http);
    }

    /** @test */
    public function check_eligibility_sends_the_bearer_and_reads_the_status_enum()
    {
        $http = (new FakeHttpClient())->queue(['data' => [
            'status' => 4, 'buyer_id' => 7, 'webview' => 'https://nasiya/register',
        ]]);

        $elig = $this->driver($http)->checkEligibility('+998 90 123 45 67');

        $this->assertSame('https://merchants-api.uzumnasiya.uz/api/v1/buyers/check-status', $http->lastRequest['url']);
        $this->assertSame('Bearer JWT', $http->lastRequest['headers']['Authorization']);
        $this->assertSame(998901234567, $http->lastRequest['payload']['phone']); // digits only, int
        $this->assertTrue($elig->isEligible());
        $this->assertSame(7, $elig->buyerId());
    }

    /** @test */
    public function eligibility_helpers_classify_register_and_blocked_codes()
    {
        $register = (new UzumNasiyaDriver(['token' => 'J'], (new FakeHttpClient())->queue(['data' => ['status' => 1]])))->checkEligibility('998900000000');
        $this->assertTrue($register->mustRegister());
        $this->assertFalse($register->isEligible());

        $blocked = (new UzumNasiyaDriver(['token' => 'J'], (new FakeHttpClient())->queue(['data' => ['status' => 8, 'is_in_black_list' => true]])))->checkEligibility('998900000000');
        $this->assertTrue($blocked->isBlocked());
    }

    /** @test */
    public function calculate_converts_item_price_to_som_and_plans_back_to_tiyin()
    {
        $http = (new FakeHttpClient())->queue(['data' => [
            ['tariff' => '12 Default', 'period_months' => 12, 'total' => 12000.0, 'month' => 1000.0, 'origin' => 11000.0, 'deposit' => 500.0, 'is_available' => true],
        ]]);

        $plans = $this->driver($http)->calculate(7, [['product_id' => 1, 'price' => 1200000, 'amount' => 1]]);

        // request: tiyin 1_200_000 -> som 12000.00
        $this->assertSame('https://merchants-api.uzumnasiya.uz/api/v1/orders/calculate', $http->lastRequest['url']);
        $this->assertSame(7, $http->lastRequest['payload']['user_id']);
        $this->assertEqualsWithDelta(12000.00, $http->lastRequest['payload']['products'][0]['price'], 0.0001);

        // response: EVERY money field som -> tiyin
        $this->assertCount(1, $plans);
        $this->assertSame('12 Default', $plans[0]->tariffId());
        $this->assertSame(12, $plans[0]->periodMonths());
        $this->assertSame(1200000, $plans[0]->totalTiyin());
        $this->assertSame(100000, $plans[0]->monthlyTiyin());
        $this->assertSame(1100000, $plans[0]->originTiyin());
        $this->assertSame(50000, $plans[0]->depositTiyin());
        $this->assertTrue($plans[0]->isAvailable());
    }

    /** @test */
    public function create_contract_maps_the_two_ids_and_converts_money()
    {
        $http = (new FakeHttpClient())->queue(['data' => [
            'paymart_client' => ['order' => 5, 'contract_id' => 9, 'total' => 12000.0, 'price_month' => 1000.0],
            'webview_path'   => 'https://nasiya/sign/9',
            'client_act_pdf' => 'https://nasiya/act/9.pdf',
        ]]);

        $contract = $this->driver($http)->createContract(7, '12 Default', [
            ['name' => 'Phone', 'price' => 1200000, 'amount' => 1, 'category' => 1, 'unit_id' => 1],
        ], 'order-1', 'https://app/return');

        $body = $http->lastRequest['payload'];
        $this->assertSame('12 Default', $body['period']);
        $this->assertSame('order-1', $body['ext_order_id']);
        $this->assertSame('https://app/return', $body['callback']);
        $this->assertEqualsWithDelta(12000.00, $body['products'][0]['price'], 0.0001);

        $this->assertSame(9, $contract->contractId());  // confirm()/status() id
        $this->assertSame(5, $contract->orderId());      // cancel() id (Nasiya `order`)
        $this->assertSame(1200000, $contract->totalTiyin());
        $this->assertSame(100000, $contract->monthlyTiyin());
        $this->assertSame('https://nasiya/sign/9', $contract->webviewPath());
    }

    /** @test */
    public function confirm_returns_ok_on_response_code_zero()
    {
        $http = (new FakeHttpClient())->queue(['response_code' => 0, 'data' => ['client_act_pdf' => 'https://nasiya/act/9-signed.pdf']]);

        $result = $this->driver($http)->confirm(9);

        $this->assertSame('https://merchants-api.uzumnasiya.uz/api/v1/contracts/confirm', $http->lastRequest['url']);
        $this->assertSame(9, $http->lastRequest['payload']['contract_id']);
        $this->assertTrue($result->isOk());
        $this->assertSame('https://nasiya/act/9-signed.pdf', $result->actPdfUrl());
    }

    /** @test */
    public function a_business_error_code_is_carried_not_thrown_and_marks_retryability()
    {
        // wrong-status (4009) on HTTP 400 -> not ok, not retryable, no throw
        $wrong = $this->driver((new FakeHttpClient())->queue(['response_code' => ContractResult::CODE_WRONG_STATUS], 400))->confirm(9);
        $this->assertFalse($wrong->isOk());
        $this->assertSame(4009, $wrong->responseCode());
        $this->assertFalse($wrong->isRetryable());

        // already-active (4010) -> not ok, not retryable
        $active = $this->driver((new FakeHttpClient())->queue(['response_code' => ContractResult::CODE_ALREADY_ACTIVE], 400))->confirm(9);
        $this->assertFalse($active->isOk());
        $this->assertSame(4010, $active->responseCode());
        $this->assertFalse($active->isRetryable());

        // technical (1000) -> retryable
        $tech = $this->driver((new FakeHttpClient())->queue(['response_code' => ContractResult::CODE_TECHNICAL], 400))->confirm(9);
        $this->assertTrue($tech->isRetryable());

        // 5xx -> retryable
        $server = $this->driver((new FakeHttpClient())->queue(['response_code' => 4005], 503))->confirm(9);
        $this->assertTrue($server->isRetryable());
    }

    /** @test */
    public function an_auth_failure_on_confirm_throws()
    {
        $http = (new FakeHttpClient())->queue(['message' => 'Unauthenticated'], 401);

        $this->expectException(BnplException::class);
        $this->driver($http)->confirm(9);
    }

    /** @test */
    public function cancel_is_keyed_on_the_order_id()
    {
        $http = (new FakeHttpClient())->queue(['response_code' => 0, 'data' => []]);

        $result = $this->driver($http)->cancel(5); // the Contract::orderId()

        $this->assertSame('https://merchants-api.uzumnasiya.uz/api/v1/contracts/cancel', $http->lastRequest['url']);
        $this->assertSame(5, $http->lastRequest['payload']['contract_id']); // order id in the contract_id field
        $this->assertTrue($result->isOk());
    }

    /** @test */
    public function status_maps_the_contract_status_enum()
    {
        $active = $this->driver((new FakeHttpClient())->queue(['data' => ['contract_status' => 1, 'is_signed' => true]]))->status(9);
        $this->assertTrue($active->isActive());
        $this->assertTrue($active->isSigned());

        $cancelled = $this->driver((new FakeHttpClient())->queue(['data' => ['contract_status' => ContractStatus::CANCELLED]]))->status(9);
        $this->assertTrue($cancelled->isCancelled());

        $overdue = $this->driver((new FakeHttpClient())->queue(['data' => ['contract_status' => ContractStatus::OVERDUE_30]]))->status(9);
        $this->assertTrue($overdue->isOverdue());
    }

    /** @test */
    public function the_tiyin_som_conversion_round_trips_a_fractional_amount()
    {
        $http = (new FakeHttpClient())->queue(['data' => [
            'paymart_client' => ['order' => 1, 'contract_id' => 1, 'total' => 12345.67, 'price_month' => 0],
        ]]);

        $contract = $this->driver($http)->createContract(7, '12', [['name' => 'X', 'price' => 1234567, 'amount' => 1]]);

        // 1_234_567 tiyin -> 12345.67 som on the wire ...
        $this->assertEqualsWithDelta(12345.67, $http->lastRequest['payload']['products'][0]['price'], 0.0001);
        // ... and 12345.67 som -> 1_234_567 tiyin back (no precision drift)
        $this->assertSame(1234567, $contract->totalTiyin());
    }

    /** @test */
    public function a_non_2xx_with_a_message_throws_a_bnpl_exception()
    {
        $http = (new FakeHttpClient())->queue(['message' => 'Forbidden'], 403);

        try {
            $this->driver($http)->checkEligibility('998900000000');
            $this->fail('Expected BnplException.');
        } catch (BnplException $e) {
            $this->assertSame('Forbidden', $e->getMessage());
            $this->assertSame(403, $e->getCode());
        }
    }

    /** @test */
    public function a_missing_token_throws_before_any_request()
    {
        $http = new FakeHttpClient(); // nothing queued
        $driver = new UzumNasiyaDriver([], $http);

        $this->expectException(BnplException::class);
        $driver->checkEligibility('998900000000');
    }

    /** @test */
    public function a_transport_fault_propagates()
    {
        $driver = new UzumNasiyaDriver(['token' => 'JWT'], new ThrowingHttpClient());

        $this->expectException(TransportException::class);
        $driver->status(9);
    }

    /** @test */
    public function calculate_create_contract_and_status_throw_on_a_non_2xx()
    {
        $ops = [
            function ($d) { return $d->calculate(7, [['price' => 1000, 'amount' => 1]]); },
            function ($d) { return $d->createContract(7, '12', [['price' => 1000, 'amount' => 1]]); },
            function ($d) { return $d->status(9); },
        ];

        foreach ($ops as $i => $op) {
            $driver = $this->driver((new FakeHttpClient())->queue(['message' => 'Bad request'], 400));
            try {
                $op($driver);
                $this->fail('Expected BnplException for op '.$i);
            } catch (BnplException $e) {
                $this->assertSame('Bad request', $e->getMessage());
                $this->assertSame(400, $e->getCode());
            }
        }
    }

    /** @test */
    public function the_error_message_falls_back_through_error_array_then_a_default()
    {
        $cases = [
            [['error' => 'flat error'], 'flat error'],
            [['error' => ['first error', 'second']], 'first error'],
            [['error' => [['field' => 'phone', 'msg' => 'bad']]], json_encode(['field' => 'phone', 'msg' => 'bad'])],
            [[], 'Uzum Nasiya request failed.'],
        ];

        foreach ($cases as $case) {
            list($body, $expected) = $case;
            try {
                $this->driver((new FakeHttpClient())->queue($body, 400))->status(9);
                $this->fail('Expected BnplException.');
            } catch (BnplException $e) {
                $this->assertSame($expected, $e->getMessage());
            }
        }
    }

    /** @test */
    public function create_contract_omits_callback_and_ext_order_id_when_not_given()
    {
        $http = (new FakeHttpClient())->queue(['data' => ['paymart_client' => ['order' => 1, 'contract_id' => 1]]]);

        $this->driver($http)->createContract(7, '12', [['name' => 'X', 'price' => 1000, 'amount' => 1]]);

        $this->assertArrayNotHasKey('callback', $http->lastRequest['payload']);
        $this->assertArrayNotHasKey('ext_order_id', $http->lastRequest['payload']);
    }

    /** @test */
    public function the_otp_methods_are_gated_on_sms_mode_and_post_to_v3()
    {
        // gated: WebView mode (default) rejects the OTP methods
        $this->expectException(BnplException::class);
        $this->driver(new FakeHttpClient())->sendSmsCode('998900000000', 9);
    }

    /** @test */
    public function send_sms_code_posts_to_v3_when_otp_mode_is_sms()
    {
        $http = (new FakeHttpClient())->queue(['data' => ['sent' => true]]);
        $driver = $this->driver($http, ['otp_mode' => 'sms']);

        $data = $driver->sendSmsCode('+998 90 000 00 00', 9);

        $this->assertSame('https://merchants-api.uzumnasiya.uz/v3/buyers/send-code-sms', $http->lastRequest['url']);
        $this->assertSame(998900000000, $http->lastRequest['payload']['phone']);
        $this->assertSame(9, $http->lastRequest['payload']['contract_id']);
        $this->assertSame(['sent' => true], $data);
    }

    /** @test */
    public function verify_sms_code_carries_a_business_code_but_throws_on_auth_failure()
    {
        // business path: 4009 carried, not thrown
        $http = (new FakeHttpClient())->queue(['response_code' => ContractResult::CODE_WRONG_STATUS], 400);
        $driver = $this->driver($http, ['otp_mode' => 'sms']);
        $result = $driver->verifySmsCode('998900000000', 9, '654321');
        $this->assertSame('https://merchants-api.uzumnasiya.uz/v3/buyers/check-code-sms', $http->lastRequest['url']);
        $this->assertSame('654321', $http->lastRequest['payload']['code']); // code is a string
        $this->assertFalse($result->isOk());

        // auth path: 401 throws
        $this->expectException(BnplException::class);
        $this->driver((new FakeHttpClient())->queue(['message' => 'Unauthenticated'], 401), ['otp_mode' => 'sms'])
            ->verifySmsCode('998900000000', 9, '654321');
    }
}
