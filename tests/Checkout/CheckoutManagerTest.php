<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Checkout\CheckoutManager;
use Goodoneuz\PayUz\Checkout\Drivers\NullDriver;
use Goodoneuz\PayUz\Checkout\Drivers\MulticardDriver;
use Goodoneuz\PayUz\Checkout\Events\PaymentFailed;
use Goodoneuz\PayUz\Checkout\Events\PaymentRefunded;
use Goodoneuz\PayUz\Checkout\Events\PaymentSucceeded;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Tests\Support\RecordingDispatcher;
use Goodoneuz\PayUz\Tests\Support\ThrowingHttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\WebhookException;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;

/**
 * Manager: driver resolution + extend(), the event-emitting helpers, and the
 * verify-then-emit webhook handler.
 */
class CheckoutManagerTest extends TestCase
{
    /** @test */
    public function it_resolves_the_default_driver_and_unknown_throws()
    {
        $this->assertInstanceOf(NullDriver::class, (new CheckoutManager(['default' => 'null']))->driver());
        $this->assertSame('null', (new CheckoutManager([]))->defaultDriver());

        $this->expectException(CheckoutException::class);
        (new CheckoutManager([]))->driver('nope');
    }

    /** @test */
    public function the_rahmat_alias_resolves_to_the_multicard_driver_with_the_multicard_config()
    {
        $http = (new FakeHttpClient())
            ->queue(['token' => 'TOK', 'expiry' => '2099-12-31 23:59:59'])
            ->queue(['success' => true, 'data' => ['uuid' => 'U1', 'status' => 'draft']]);

        $manager = new CheckoutManager([
            'drivers' => ['multicard' => ['base_url' => 'https://dev-mesh.multicard.uz', 'application_id' => 'a', 'secret' => 's', 'store_id' => 42]],
        ], $http);

        $driver = $manager->driver('rahmat');
        $this->assertInstanceOf(MulticardDriver::class, $driver);
        $this->assertSame('multicard', $driver->name()); // it IS the Multicard rail

        // and it uses the `multicard` config (store_id 42 reaches the wire)
        $manager->pay(Payment::make(1000, 'order-1'), 'rahmat');
        $this->assertSame(42, $http->requests[1]['payload']['store_id']);
    }

    /** @test */
    public function pay_creates_a_redirectable_payment_without_emitting()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new CheckoutManager(['default' => 'null'], null, $dispatcher);

        $result = $manager->pay(Payment::make(1200000, 'order-1'));

        $this->assertTrue($result->needsRedirect());
        $this->assertCount(0, $dispatcher->events); // not paid yet
    }

    /** @test */
    public function charge_capture_and_refund_emit_the_matching_events()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new CheckoutManager(['default' => 'null'], null, $dispatcher);

        $manager->charge('tok', Payment::make(1200000, 'order-1'));
        $manager->capture('p1', 1200000);
        $manager->refund('p1', 1200000);

        $this->assertCount(2, $dispatcher->ofType(PaymentSucceeded::class)); // charge + capture
        $this->assertCount(1, $dispatcher->ofType(PaymentRefunded::class));
    }

    /** @test */
    public function webhook_verifies_then_normalizes_and_emits()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new CheckoutManager(['default' => 'null'], null, $dispatcher);

        $result = $manager->webhook(['status' => PaymentResult::STATUS_SUCCEEDED, 'payment_id' => 'p9']);

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $dispatcher->ofType(PaymentSucceeded::class));
    }

    /** @test */
    public function a_failed_result_emits_payment_failed_only()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new CheckoutManager([], null, $dispatcher);

        $manager->extend('failing', function () {
            return new class implements CheckoutDriver {
                public function createPayment(Payment $payment) { return new PaymentResult(PaymentResult::STATUS_CREATED); }
                public function chargeToken($token, Payment $payment) { return new PaymentResult(PaymentResult::STATUS_FAILED); }
                public function capture($paymentId, $amount = null) { return new PaymentResult(PaymentResult::STATUS_CANCELLED); }
                public function refund($paymentId, $amount = null) { return new PaymentResult(PaymentResult::STATUS_REFUNDED); }
                public function status($reference) { return new PaymentResult(PaymentResult::STATUS_PENDING); }
                public function verifyWebhook(array $payload, array $headers = []) { return true; }
                public function parseWebhook(array $payload) { return new PaymentResult(PaymentResult::STATUS_SUCCEEDED); }
                public function name() { return 'failing'; }
            };
        });

        $manager->charge('tok', Payment::make(1000, 'o'), 'failing');   // FAILED
        $manager->capture('p1', 1000, 'failing');                       // CANCELLED -> isFailed

        $this->assertCount(2, $dispatcher->ofType(PaymentFailed::class));
        $this->assertCount(0, $dispatcher->ofType(PaymentSucceeded::class));
    }

    /** @test */
    public function a_driver_fault_propagates_through_helpers_without_emitting()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new CheckoutManager(
            ['default' => 'octo', 'drivers' => ['octo' => ['shop_id' => 1, 'secret' => 's']]],
            new ThrowingHttpClient(),
            $dispatcher
        );

        try {
            $manager->charge('tok', Payment::make(1000, 'o'));
            $this->fail('Expected the transport fault to propagate.');
        } catch (TransportException $e) {
            $this->assertCount(0, $dispatcher->events); // no event on a thrown call
        }
    }

    /** @test */
    public function manager_webhook_enforces_a_real_octo_signature_before_emitting()
    {
        $config = ['default' => 'octo', 'drivers' => ['octo' => ['shop_id' => 1, 'secret' => 's', 'unique_key' => 'uk']]];

        // valid signature -> verified, normalized, emitted
        $ok = new RecordingDispatcher();
        $manager = new CheckoutManager($config, new FakeHttpClient(), $ok);
        $payload = ['octo_payment_UUID' => 'U1', 'status' => 'succeeded'];
        $payload['signature'] = sha1('uk'.'U1'.'succeeded');
        $this->assertTrue($manager->webhook($payload)->isSuccessful());
        $this->assertCount(1, $ok->ofType(PaymentSucceeded::class));

        // tampered signature -> rejected, nothing emitted
        $bad = new RecordingDispatcher();
        $manager2 = new CheckoutManager($config, new FakeHttpClient(), $bad);
        $payload['signature'] = 'tampered';
        try {
            $manager2->webhook($payload);
            $this->fail('Expected WebhookException.');
        } catch (WebhookException $e) {
            $this->assertCount(0, $bad->events);
        }
    }

    /** @test */
    public function a_webhook_failing_verification_throws_and_emits_nothing()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new CheckoutManager([], null, $dispatcher);

        // a driver that rejects every webhook signature
        $manager->extend('rejecting', function () {
            return new class implements CheckoutDriver {
                public function createPayment(Payment $payment) { return new PaymentResult(PaymentResult::STATUS_CREATED); }
                public function chargeToken($token, Payment $payment) { return new PaymentResult(PaymentResult::STATUS_SUCCEEDED); }
                public function capture($paymentId, $amount = null) { return new PaymentResult(PaymentResult::STATUS_SUCCEEDED); }
                public function refund($paymentId, $amount = null) { return new PaymentResult(PaymentResult::STATUS_REFUNDED); }
                public function status($paymentId) { return new PaymentResult(PaymentResult::STATUS_PENDING); }
                public function verifyWebhook(array $payload, array $headers = []) { return false; }
                public function parseWebhook(array $payload) { return new PaymentResult(PaymentResult::STATUS_SUCCEEDED); }
                public function name() { return 'rejecting'; }
            };
        });

        try {
            $manager->webhook(['status' => 'succeeded'], [], 'rejecting');
            $this->fail('Expected WebhookException.');
        } catch (WebhookException $e) {
            $this->assertCount(0, $dispatcher->events); // never acted on an unverified payload
        }
    }
}
