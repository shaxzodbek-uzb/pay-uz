<?php

namespace Goodoneuz\PayUz\Tests\Payments;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Services\PaymentService;
use Goodoneuz\PayUz\Payments\DefaultPaymentResolver;
use Goodoneuz\PayUz\Payments\Events\PaymentPaid;
use Goodoneuz\PayUz\Payments\Events\PaymentBeforePay;
use Goodoneuz\PayUz\Payments\Events\PaymentCancelled;
use Goodoneuz\PayUz\Payments\Events\PaymentProcessing;

/**
 * PaymentService no longer require()s editable PHP files. The value-returning
 * methods must delegate to the configured resolver, and payListener() must
 * dispatch the lifecycle event matching its action type — both verifiable without
 * a database, container or filesystem.
 */
class PaymentServiceTest extends TestCase
{
    /** @var FakePaymentResolver */
    private $resolver;

    /** @var RecordingDispatcher */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->resolver   = new FakePaymentResolver();
        $this->dispatcher = new RecordingDispatcher();

        PaymentService::useResolver($this->resolver);
        PaymentService::useDispatcher($this->dispatcher);
    }

    protected function tearDown(): void
    {
        PaymentService::reset();
    }

    /** @test */
    public function convert_model_to_key_delegates_to_the_resolver()
    {
        $model = (object) ['id' => 42];

        $this->assertSame('the-key', PaymentService::convertModelToKey($model));
        $this->assertSame(['convertModelToKey', $model], $this->resolver->calls[0]);
    }

    /** @test */
    public function convert_key_to_model_delegates_to_the_resolver()
    {
        $this->assertSame('the-model', PaymentService::convertKeyToModel('abc'));
        $this->assertSame(['convertKeyToModel', 'abc'], $this->resolver->calls[0]);
    }

    /** @test */
    public function is_proper_model_and_amount_delegates_to_the_resolver()
    {
        $this->resolver->proper = false;

        $this->assertFalse(PaymentService::isProperModelAndAmount('m', 1500));
        $this->assertSame(['isProperModelAndAmount', 'm', 1500], $this->resolver->calls[0]);
    }

    /** @test */
    public function before_response_delegates_and_returns_the_resolver_result()
    {
        $this->resolver->response = ['patched' => true];

        $out = PaymentService::beforeResponse('Click@Prepare', ['a' => 1], ['orig' => 1]);

        $this->assertSame(['patched' => true], $out);
        $this->assertSame(['beforeResponse', 'Click@Prepare', ['a' => 1], ['orig' => 1]], $this->resolver->calls[0]);
    }

    /**
     * @test
     * @dataProvider actionEventProvider
     */
    public function pay_listener_dispatches_the_event_for_each_action_type($action, $eventClass)
    {
        $model       = (object) ['id' => 7];
        $transaction = (object) ['id' => 99];

        PaymentService::payListener($model, $transaction, $action);

        $events = $this->dispatcher->ofType($eventClass);
        $this->assertCount(1, $events, "expected one {$eventClass} for action {$action}");
        $this->assertSame($model, $events[0]->model);
        $this->assertSame($transaction, $events[0]->transaction);
    }

    public function actionEventProvider()
    {
        return [
            'before-pay' => ['before-pay', PaymentBeforePay::class],
            'paying'     => ['paying', PaymentProcessing::class],
            'after-pay'  => ['after-pay', PaymentPaid::class],
            'cancel-pay' => ['cancel-pay', PaymentCancelled::class],
        ];
    }

    /** @test */
    public function pay_listener_ignores_unknown_action_types()
    {
        PaymentService::payListener((object) [], (object) [], 'no-such-action');

        $this->assertCount(0, $this->dispatcher->events);
    }

    /** @test */
    public function pay_listener_does_not_dispatch_unrelated_events()
    {
        PaymentService::payListener(null, 1000, 'before-pay');

        $this->assertCount(1, $this->dispatcher->events);
        $this->assertCount(0, $this->dispatcher->ofType(PaymentPaid::class));
        // before-pay carries the amount in the transaction slot.
        $this->assertSame(1000, $this->dispatcher->ofType(PaymentBeforePay::class)[0]->transaction);
    }

    /** @test */
    public function resolver_falls_back_to_the_default_when_nothing_is_configured()
    {
        PaymentService::reset(); // drop the test override; no container binding either

        $this->assertInstanceOf(DefaultPaymentResolver::class, PaymentService::resolver());
    }
}
