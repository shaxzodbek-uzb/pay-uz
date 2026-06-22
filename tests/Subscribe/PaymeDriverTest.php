<?php

namespace Goodoneuz\PayUz\Tests\Subscribe;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Subscribe\Charge;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Subscribe\Drivers\PaymeDriver;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Subscribe\Exceptions\AccountException;
use Goodoneuz\PayUz\Subscribe\Exceptions\OperationException;
use Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException;
use Goodoneuz\PayUz\Subscribe\Exceptions\CancellationException;
use Goodoneuz\PayUz\Subscribe\Exceptions\InvalidAmountException;
use Goodoneuz\PayUz\Subscribe\Exceptions\AuthorizationException;
use Goodoneuz\PayUz\Subscribe\Exceptions\ReceiptNotFoundException;

/**
 * Payme Subscribe driver: the X-Auth-per-method rule, JSON-RPC request shapes,
 * result parsing and error → typed-exception mapping — all against a fake
 * transport, no network.
 */
class PaymeDriverTest extends TestCase
{
    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        $config = array_merge(['merchant_id' => 'mid123', 'key' => 'secretkey', 'test' => true], $overrides);

        return new PaymeDriver($config, $http);
    }

    private function lastMethod(FakeHttpClient $http)
    {
        return $http->lastRequest['payload']['method'];
    }

    private function lastAuth(FakeHttpClient $http)
    {
        return $http->lastRequest['headers']['X-Auth'];
    }

    /** @test */
    public function create_card_uses_the_id_only_auth_and_the_sandbox_endpoint()
    {
        $http = (new FakeHttpClient())->queue(['result' => ['card' => [
            'number' => '8600****6311', 'expire' => '03/99', 'token' => 'tok_1', 'recurrent' => true, 'verify' => false,
        ]]]);

        $card = $this->driver($http)->createCard('8600123412346311', '0399', true);

        $this->assertSame('https://checkout.test.paycom.uz/api', $http->lastRequest['url']);
        $this->assertSame('cards.create', $this->lastMethod($http));
        $this->assertSame('mid123', $this->lastAuth($http)); // id only — browser-safe
        $this->assertSame(['number' => '8600123412346311', 'expire' => '0399'], $http->lastRequest['payload']['params']['card']);
        $this->assertTrue($http->lastRequest['payload']['params']['save']);
        $this->assertSame('tok_1', $card->token());
        $this->assertFalse($card->isVerified());
    }

    /** @test */
    public function send_verify_code_and_verify_use_id_only_auth()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['sent' => true, 'phone' => '99890*****31', 'wait' => 60000]])
            ->queue(['result' => ['card' => ['token' => 'tok_1', 'verify' => true]]]);
        $driver = $this->driver($http);

        $vc = $driver->sendVerifyCode('tok_1');
        $this->assertSame('cards.get_verify_code', $this->lastMethod($http));
        $this->assertSame('mid123', $this->lastAuth($http));
        $this->assertTrue($vc->wasSent());
        $this->assertSame(60000, $vc->wait());

        $card = $driver->verifyCard('tok_1', '666666');
        $this->assertSame('cards.verify', $this->lastMethod($http));
        $this->assertSame('mid123', $this->lastAuth($http));
        $this->assertSame('666666', $http->lastRequest['payload']['params']['code']);
        $this->assertTrue($card->isVerified());
    }

    /** @test */
    public function check_and_remove_card_are_server_side_and_use_id_key_auth()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['card' => ['token' => 'tok_1', 'verify' => true]]])
            ->queue(['result' => ['success' => true]]);
        $driver = $this->driver($http);

        $driver->checkCard('tok_1');
        $this->assertSame('cards.check', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http)); // secret key required

        $removed = $driver->removeCard('tok_1');
        $this->assertSame('cards.remove', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http));
        $this->assertTrue($removed);
    }

    /** @test */
    public function create_and_pay_receipt_use_id_key_auth_with_tiyin_amount()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['receipt' => ['_id' => 'rcp1', 'state' => 0, 'amount' => 1200000]]])
            ->queue(['result' => ['receipt' => ['_id' => 'rcp1', 'state' => 4, 'card' => ['number' => '8600****0000']]]]);
        $driver = $this->driver($http);

        $created = $driver->createReceipt(1200000, ['order_id' => 42], ['description' => 'Pro plan']);
        $this->assertSame('receipts.create', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http));
        $this->assertSame(1200000, $http->lastRequest['payload']['params']['amount']);
        $this->assertSame(['order_id' => 42], $http->lastRequest['payload']['params']['account']);
        $this->assertSame('Pro plan', $http->lastRequest['payload']['params']['description']);
        $this->assertSame('rcp1', $created->id());

        $paid = $driver->payReceipt('rcp1', 'tok_1');
        $this->assertSame('receipts.pay', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http)); // money move — secret key
        $this->assertSame('rcp1', $http->lastRequest['payload']['params']['id']);
        $this->assertSame('tok_1', $http->lastRequest['payload']['params']['token']);
        $this->assertTrue($paid->isPaid());
    }

    /** @test */
    public function cancel_and_get_receipt_are_server_side_and_parse_state()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['receipt' => ['_id' => 'r9', 'state' => Charge::STATE_CANCEL_QUEUED]]])
            ->queue(['result' => ['receipt' => ['_id' => 'r9', 'state' => Charge::STATE_PAID]]]);
        $driver = $this->driver($http);

        $cancelled = $driver->cancelReceipt('r9');
        $this->assertSame('receipts.cancel', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http));
        $this->assertTrue($cancelled->isCancelled()); // state 21 counts as cancelling

        $driver->getReceipt('r9');
        $this->assertSame('receipts.get', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http));
    }

    /** @test */
    public function hold_flag_is_sent_on_both_create_and_pay()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['receipt' => ['_id' => 'h1', 'state' => 0]]])
            ->queue(['result' => ['receipt' => ['_id' => 'h1', 'state' => 5]]]);
        $driver = $this->driver($http);

        $driver->createReceipt(2500, ['order_id' => 7], ['hold' => true]);
        $this->assertTrue($http->lastRequest['payload']['params']['hold']);

        $held = $driver->payReceipt('h1', 'tok_1', ['hold' => true]);
        $this->assertTrue($http->lastRequest['payload']['params']['hold']);
        $this->assertTrue($held->isHeld());
    }

    /** @test */
    public function check_receipt_returns_the_state_code_and_confirm_hold_captures()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['state' => Charge::STATE_PAID]])
            ->queue(['result' => ['receipt' => ['_id' => 'h1', 'state' => Charge::STATE_PAID]]]);
        $driver = $this->driver($http);

        $this->assertSame(Charge::STATE_PAID, $driver->checkReceipt('h1'));
        $this->assertSame('receipts.check', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http));

        $captured = $driver->confirmHold('h1');
        $this->assertSame('receipts.confirm_hold', $this->lastMethod($http));
        $this->assertSame('mid123:secretkey', $this->lastAuth($http)); // capture moves money
        $this->assertTrue($captured->isPaid());
    }

    /** @test */
    public function the_two_stage_hold_flow_transitions_held_then_captured()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['receipt' => ['_id' => 'h1', 'state' => Charge::STATE_CREATED]]])
            ->queue(['result' => ['receipt' => ['_id' => 'h1', 'state' => Charge::STATE_HELD]]])
            ->queue(['result' => ['receipt' => ['_id' => 'h1', 'state' => Charge::STATE_PAID]]]);
        $driver = $this->driver($http);

        $driver->createReceipt(2500, ['order_id' => 7], ['hold' => true]);
        $held = $driver->payReceipt('h1', 'tok_1', ['hold' => true]);
        $this->assertTrue($held->isHeld());

        $captured = $driver->confirmHold('h1');
        $this->assertTrue($captured->isPaid());
    }

    /** @test */
    public function insufficient_privilege_maps_to_authorization_exception()
    {
        $http = (new FakeHttpClient())->queue(['error' => ['code' => -32504, 'message' => 'Insufficient privilege']]);

        $this->expectException(AuthorizationException::class);
        $this->driver($http)->payReceipt('r1', 'tok_1');
    }

    /** @test */
    public function transaction_not_found_maps_to_receipt_not_found_exception()
    {
        $http = (new FakeHttpClient())->queue(['error' => ['code' => -31003, 'message' => 'not found']]);

        $this->expectException(ReceiptNotFoundException::class);
        $this->driver($http)->getReceipt('missing');
    }

    /** @test */
    public function account_errors_map_to_account_exception()
    {
        $http = (new FakeHttpClient())->queue(['error' => ['code' => -31050, 'message' => 'order not found']]);

        $this->expectException(AccountException::class);
        $this->driver($http)->createReceipt(1000, ['order_id' => 'nope']);
    }

    /** @test */
    public function verified_error_codes_map_to_their_typed_exceptions()
    {
        $cases = [
            [-31001, InvalidAmountException::class],
            [-31007, CancellationException::class],
            [-31008, OperationException::class],
        ];

        foreach ($cases as $case) {
            list($code, $expected) = $case;
            $http = (new FakeHttpClient())->queue(['error' => ['code' => $code, 'message' => 'x']]);
            try {
                $this->driver($http)->getReceipt('r1');
                $this->fail('Expected '.$expected.' for code '.$code);
            } catch (SubscribeException $e) {
                $this->assertInstanceOf($expected, $e);
                $this->assertSame($code, $e->getCode());
            }
        }
    }

    /** @test */
    public function the_account_error_range_is_bounded_inclusively()
    {
        // -31050..-31099 inclusive => AccountException; just outside => base.
        $this->assertInstanceOf(AccountException::class, $this->mapError(-31099));
        $this->assertNotInstanceOf(AccountException::class, $this->mapError(-31100));
        $this->assertNotInstanceOf(AccountException::class, $this->mapError(-31049));
    }

    /** @test */
    public function a_transport_fault_propagates_and_is_not_wrapped_as_a_subscribe_exception()
    {
        $driver = new PaymeDriver(['merchant_id' => 'mid123', 'key' => 'k'], new ThrowingHttpClient());

        $this->expectException(TransportException::class);
        $driver->payReceipt('r1', 'tok_1');
    }

    /**
     * Map a gateway error code to the SubscribeException subclass the driver
     * raises for it.
     *
     * @param int $code
     * @return SubscribeException
     */
    private function mapError($code)
    {
        $http = (new FakeHttpClient())->queue(['error' => ['code' => $code, 'message' => 'x']]);
        try {
            $this->driver($http)->getReceipt('r1');
        } catch (SubscribeException $e) {
            return $e;
        }
        $this->fail('Expected a SubscribeException for code '.$code);
    }

    /** @test */
    public function an_unmapped_code_surfaces_as_a_generic_subscribe_exception_carrying_the_code()
    {
        // e.g. a card-decline code, whose exact number is not publicly documented.
        $http = (new FakeHttpClient())->queue(['error' => ['code' => -31630, 'message' => 'card declined']]);

        try {
            $this->driver($http)->payReceipt('r1', 'tok_1');
            $this->fail('Expected SubscribeException.');
        } catch (SubscribeException $e) {
            $this->assertSame(-31630, $e->getCode());
            $this->assertSame('card declined', $e->getMessage());
        }
    }

    /** @test */
    public function missing_merchant_id_throws_before_any_request()
    {
        $http = new FakeHttpClient(); // nothing queued -> would throw if reached
        $driver = $this->driver($http, ['merchant_id' => '']);

        $this->expectException(SubscribeException::class);
        $driver->createCard('8600000000000000', '0399');
    }

    /** @test */
    public function a_server_side_method_without_a_key_throws_before_any_request()
    {
        $http = new FakeHttpClient();
        $driver = $this->driver($http, ['key' => '']);

        $this->expectException(SubscribeException::class);
        $driver->checkCard('tok_1'); // server-side method needs the secret key
    }

    /** @test */
    public function production_endpoint_is_used_when_not_in_test_mode()
    {
        $http = (new FakeHttpClient())->queue(['result' => ['card' => ['token' => 't']]]);
        $this->driver($http, ['test' => false])->createCard('8600000000000000', '0399');

        $this->assertSame('https://checkout.paycom.uz/api', $http->lastRequest['url']);
    }
}
