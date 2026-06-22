<?php

namespace Goodoneuz\PayUz\Tests\Subscribe;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Subscribe\SubscribeManager;
use Goodoneuz\PayUz\Subscribe\Drivers\NullDriver;
use Goodoneuz\PayUz\Subscribe\Events\ChargePaid;
use Goodoneuz\PayUz\Subscribe\Events\CardVerified;
use Goodoneuz\PayUz\Subscribe\Events\HoldConfirmed;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use Goodoneuz\PayUz\Tests\Support\RecordingDispatcher;
use Goodoneuz\PayUz\Subscribe\Events\ChargeCancelled;
use Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException;

/**
 * Manager: driver resolution + extend(), and the event-emitting helpers
 * (verify / charge / authorize / capture / release).
 */
class SubscribeManagerTest extends TestCase
{
    /** @test */
    public function it_resolves_the_default_driver_and_falls_back_to_null()
    {
        $this->assertInstanceOf(NullDriver::class, (new SubscribeManager(['default' => 'null']))->driver());
        $this->assertSame('null', (new SubscribeManager([]))->defaultDriver());
    }

    /** @test */
    public function an_unknown_driver_throws()
    {
        $this->expectException(SubscribeException::class);
        (new SubscribeManager([]))->driver('nope');
    }

    /** @test */
    public function extend_registers_a_custom_driver_with_its_config()
    {
        $manager = new SubscribeManager(['drivers' => ['fake' => ['k' => 'v']]]);

        $captured = null;
        $manager->extend('fake', function ($config, $http) use (&$captured) {
            $captured = $config;
            return new NullDriver();
        });

        $this->assertInstanceOf(NullDriver::class, $manager->driver('fake'));
        $this->assertSame(['k' => 'v'], $captured);
    }

    /** @test */
    public function verify_emits_card_verified()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new SubscribeManager(['default' => 'null'], null, $dispatcher);

        $card = $manager->verify('tok_1', '666666');

        $this->assertTrue($card->isVerified());
        $this->assertCount(1, $dispatcher->ofType(CardVerified::class));
    }

    /** @test */
    public function charge_creates_then_pays_and_emits_charge_paid()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new SubscribeManager(['default' => 'null'], null, $dispatcher);

        $charge = $manager->charge('tok_1', 1200000, ['order_id' => 42]);

        $this->assertTrue($charge->isPaid());
        $this->assertCount(1, $dispatcher->ofType(ChargePaid::class));
        $this->assertSame('null', $dispatcher->ofType(ChargePaid::class)[0]->driver);
    }

    /** @test */
    public function charge_ignores_a_stray_hold_option_and_still_captures()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new SubscribeManager(['default' => 'null'], null, $dispatcher);

        // A leftover hold=true must NOT silently turn charge() into an authorize.
        $charge = $manager->charge('tok_1', 1200000, ['order_id' => 42], ['hold' => true]);

        $this->assertTrue($charge->isPaid());
        $this->assertFalse($charge->isHeld());
        $this->assertCount(1, $dispatcher->ofType(ChargePaid::class));
    }

    /** @test */
    public function charge_cancels_the_orphaned_receipt_when_payment_is_declined()
    {
        $http = (new FakeHttpClient())
            ->queue(['result' => ['receipt' => ['_id' => 'rcp1', 'state' => 0]]])   // createReceipt
            ->queue(['error' => ['code' => -31630, 'message' => 'card declined']])  // payReceipt declines
            ->queue(['result' => ['receipt' => ['_id' => 'rcp1', 'state' => 21]]]); // cleanup cancel

        $manager = new SubscribeManager(
            ['default' => 'payme', 'drivers' => ['payme' => ['merchant_id' => 'mid', 'key' => 'k']]],
            $http
        );

        try {
            $manager->charge('tok_1', 1200000, ['order_id' => 42]);
            $this->fail('Expected the decline to propagate.');
        } catch (SubscribeException $e) {
            $this->assertSame(-31630, $e->getCode());
        }

        $methods = array_map(function ($r) {
            return $r['payload']['method'];
        }, $http->requests);

        $this->assertSame(['receipts.create', 'receipts.pay', 'receipts.cancel'], $methods);
    }

    /** @test */
    public function authorize_holds_without_emitting_charge_paid()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new SubscribeManager(['default' => 'null'], null, $dispatcher);

        $charge = $manager->authorize('tok_1', 2500, ['order_id' => 7]);

        $this->assertTrue($charge->isHeld());
        $this->assertCount(0, $dispatcher->ofType(ChargePaid::class));
    }

    /** @test */
    public function capture_emits_hold_confirmed_and_release_emits_charge_cancelled()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new SubscribeManager(['default' => 'null'], null, $dispatcher);

        $this->assertTrue($manager->capture('h1')->isPaid());
        $this->assertCount(1, $dispatcher->ofType(HoldConfirmed::class));

        $this->assertTrue($manager->release('h1')->isCancelled());
        $this->assertCount(1, $dispatcher->ofType(ChargeCancelled::class));
    }
}
