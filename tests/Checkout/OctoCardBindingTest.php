<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use Goodoneuz\PayUz\Checkout\BoundCard;
use Goodoneuz\PayUz\Checkout\CardBinding;
use Goodoneuz\PayUz\Checkout\Contracts\CardBinder;
use Goodoneuz\PayUz\Checkout\Drivers\OctoDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Octo card binding: /bind_card → /verificationInfo → /bind_card/check_sms_key,
 * then the callback that finally carries the token. All against a fake transport.
 */
class OctoCardBindingTest extends TestCase
{
    private function driver(FakeHttpClient $http, array $overrides = [])
    {
        $config = array_merge([
            'shop_id'         => 42,
            'secret'          => 'sec',
            'unique_key'      => 'uk',
            'test'            => true,
            'return_url'      => 'https://app/return',
            'notify_url'      => 'https://app/webhook',
            'bind_notify_url' => 'https://app/cards/callback',
            'language'        => 'uz',
        ], $overrides);

        return new OctoDriver($config, $http);
    }

    private function binding()
    {
        return CardBinding::make('8600 1234 5678 9012', '1229', 'ref-uuid-1')
            ->heldBy('ALISHER KARIMOV')
            ->withPhone('+998901112233')
            ->withCvc('123');
    }

    /** @test */
    public function the_octo_driver_advertises_card_binding()
    {
        $this->assertInstanceOf(CardBinder::class, $this->driver(new FakeHttpClient()));
    }

    /** @test */
    public function start_binding_posts_the_card_and_returns_the_code_screen_data()
    {
        $http = (new FakeHttpClient())
            ->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'BIND-1', 'status' => 'created']])
            ->queue(['error' => 0, 'data' => ['verifyId' => 777, 'phone' => '99890*****33', 'secondsLeft' => 120]]);

        $session = $this->driver($http)->startBinding($this->binding());

        $bind = $http->requests[0];
        $this->assertSame('https://secure.octo.uz/bind_card', $bind['url']);
        $this->assertSame(42, $bind['payload']['octo_shop_id']);
        $this->assertSame('8600123456789012', $bind['payload']['pan']);
        // "MMYY" in, "YYMM" out — the order Octo's `exp` field uses.
        $this->assertSame('2912', $bind['payload']['exp']);
        $this->assertSame('uzcard', $bind['payload']['method']);
        // normalised: Octo rejects the +998… form the app stores
        $this->assertSame('998901112233', $bind['payload']['phone']);
        $this->assertSame('ref-uuid-1', $bind['payload']['shop_transaction_id']);
        $this->assertSame('https://app/cards/callback', $bind['payload']['bind_notify_url']);

        $this->assertSame('GET', $http->requests[1]['method']);
        $this->assertSame('https://secure.octo.uz/verificationInfo/BIND-1', $http->requests[1]['url']);

        $this->assertSame('BIND-1', $session->bindingId());
        $this->assertSame(777, $session->verifyId());
        $this->assertSame('99890*****33', $session->phone());
        $this->assertSame(120, $session->secondsLeft());
        $this->assertTrue($session->isOpen());
        // Carried through from the card so callers can render it before the gateway
        // echoes anything back.
        $this->assertSame('860012', $session->firstSix());
        $this->assertSame('9012', $session->lastFour());
    }

    /** @test */
    public function a_card_octo_cannot_tokenize_is_refused_before_any_request()
    {
        $http = new FakeHttpClient();

        $this->expectException(CheckoutException::class);

        try {
            $this->driver($http)->startBinding(CardBinding::make('4242424242424242', '1229', 'ref-2')->withPhone('+998901112233'));
        } finally {
            // Binding debits the card for real, so a card that cannot be bound must
            // never reach Octo in the first place.
            $this->assertSame([], $http->requests);
        }
    }

    /**
     * The published field table marks both callback URLs optional, but the live API
     * rejects /bind_card outright without them — before it even looks at the shop.
     *
     * @test
     * @dataProvider missingCallbackUrl
     */
    public function binding_without_either_callback_url_is_refused($missing)
    {
        $http = new FakeHttpClient();
        $driver = $this->driver($http, [$missing => null]);

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage($missing);

        try {
            $driver->startBinding($this->binding());
        } finally {
            $this->assertSame([], $http->requests);
        }
    }

    public function missingCallbackUrl()
    {
        return [['notify_url'], ['bind_notify_url']];
    }

    /**
     * The live API takes exactly one phone shape — 12 digits, no `+`, no spaces —
     * and answers "Wrong phone format" for anything else.
     *
     * @test
     * @dataProvider phoneShapes
     */
    public function the_phone_is_normalised_to_the_only_shape_octo_accepts($given, $expected)
    {
        $http = (new FakeHttpClient())
            ->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'BIND-1']])
            ->queue(['error' => 0, 'data' => ['verifyId' => 1, 'phone' => 'x', 'secondsLeft' => 60]]);

        $binding = CardBinding::make('8600123456789012', '1229', 'ref')->withPhone($given);
        $this->driver($http)->startBinding($binding);

        $this->assertSame($expected, $http->requests[0]['payload']['phone']);
    }

    public function phoneShapes()
    {
        return [
            'plus prefixed'  => ['+998901112233', '998901112233'],
            'spaced'         => ['+998 90 111 22 33', '998901112233'],
            'already plain'  => ['998901112233', '998901112233'],
            'national only'  => ['901112233', '998901112233'],
        ];
    }

    /** @test */
    public function optional_card_fields_are_omitted_rather_than_sent_empty()
    {
        $http = (new FakeHttpClient())
            ->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'BIND-1']])
            ->queue(['error' => 0, 'data' => ['verifyId' => 1, 'phone' => 'x', 'secondsLeft' => 60]]);

        $binding = CardBinding::make('8600123456789012', '1229', 'ref')->withPhone('998901112233');
        $this->driver($http)->startBinding($binding);

        $body = $http->requests[0]['payload'];
        $this->assertArrayNotHasKey('cardHolderName', $body);
        $this->assertArrayNotHasKey('cvc2', $body);
    }

    /** @test */
    public function binding_without_a_phone_is_refused()
    {
        $this->expectException(CheckoutException::class);
        $this->driver(new FakeHttpClient())->startBinding(CardBinding::make('8600123456789012', '1229', 'ref-3'));
    }

    /** @test */
    public function confirm_binding_sends_the_code_under_octos_odd_field_name()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => [
            'status' => 'succeeded', 'first6' => '860012', 'last4' => '9012',
        ]]);

        $session = $this->driver($http)->confirmBinding('BIND-1', 777, '1234');

        $this->assertSame('https://secure.octo.uz/bind_card/check_sms_key', $http->lastRequest['url']);
        $body = $http->lastRequest['payload'];
        // Every other endpoint spells this octo_payment_UUID.
        $this->assertSame('BIND-1', $body['paymentUUID']);
        $this->assertSame(777, $body['verifyId']);
        $this->assertSame('1234', $body['smsKey']);

        $this->assertTrue($session->isConfirmed());
        $this->assertSame('9012', $session->lastFour());
    }

    /** @test */
    public function a_rejected_code_throws_so_the_caller_can_let_the_customer_retry()
    {
        $http = (new FakeHttpClient())->queue(['error' => -270, 'errMessage' => 'Неверный код подтверждения']);

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('Неверный код подтверждения');

        $this->driver($http)->confirmBinding('BIND-1', 777, '0000');
    }

    /** @test */
    public function the_binding_callback_yields_a_token_and_never_the_card_number()
    {
        $card = $this->driver(new FakeHttpClient())->parseBindCallback([
            'shop_transaction_id' => 'ref-uuid-1',
            'pan'                 => '8600123456789012',
            'exp'                 => '2912',
            'cardHolderName'      => 'ALISHER KARIMOV',
            'card_token'          => 'octo-token-abc',
            'status'              => 'active',
        ]);

        $this->assertTrue($card->isBound());
        $this->assertSame('octo-token-abc', $card->token());
        $this->assertSame('ref-uuid-1', $card->reference());
        $this->assertSame('860012', $card->firstSix());
        $this->assertSame('9012', $card->lastFour());

        // The callback carries a full PAN; nothing that leaves this object may.
        $this->assertArrayNotHasKey('pan', $card->raw());
        $this->assertStringNotContainsString('8600123456789012', json_encode($card->toArray()));
        $this->assertStringNotContainsString('8600123456789012', json_encode($card->raw()));
    }

    /** @test */
    public function a_failed_binding_callback_yields_no_token()
    {
        $card = $this->driver(new FakeHttpClient())->parseBindCallback([
            'shop_transaction_id' => 'ref-uuid-1',
            'status'              => 'failed',
        ]);

        $this->assertFalse($card->isBound());
        $this->assertNull($card->token());
    }

    /** @test */
    public function the_acknowledgement_is_exactly_what_octo_waits_for()
    {
        // Octo retries three times and then cancels the token if it never sees this.
        $this->assertSame(
            ['status' => 'success', 'message' => 'Callback processed successfully'],
            BoundCard::acknowledgement()
        );
    }

    /** @test */
    public function revoking_a_token_blocks_it_at_octo()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'status' => 'blocked']);

        $this->assertTrue($this->driver($http)->revokeToken('octo-token-abc'));
        $this->assertSame('https://secure.octo.uz/block_card_token', $http->lastRequest['url']);
        $this->assertSame('octo-token-abc', $http->lastRequest['payload']['card_token']);
    }

    /** @test */
    public function bindable_methods_default_to_the_documented_local_schemes_and_stay_configurable()
    {
        $this->assertSame(['uzcard', 'humo'], $this->driver(new FakeHttpClient())->bindableMethods());

        $widened = $this->driver(new FakeHttpClient(), ['bindable_methods' => ['uzcard', 'humo', 'bank_card']]);
        $this->assertSame(['uzcard', 'humo', 'bank_card'], $widened->bindableMethods());
    }

    /** @test */
    public function the_pan_stays_out_of_debug_output()
    {
        $dump = print_r($this->binding(), true);

        $this->assertStringNotContainsString('8600123456789012', $dump);
        $this->assertStringNotContainsString('123', substr($dump, strpos($dump, 'cvc')));
    }
}
