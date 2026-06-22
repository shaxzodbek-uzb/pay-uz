<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;
use Goodoneuz\PayUz\Fiscalization\Drivers\NullDriver;
use Goodoneuz\PayUz\Fiscalization\Contracts\FiscalDriver;
use Goodoneuz\PayUz\Fiscalization\FiscalizationManager;
use Goodoneuz\PayUz\Fiscalization\Events\ReceiptFiscalized;
use Goodoneuz\PayUz\Fiscalization\Events\FiscalizationFailed;
use Goodoneuz\PayUz\Fiscalization\Exceptions\FiscalizationException;
use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * Manager: driver resolution from config, custom drivers via extend(), and the
 * event-emitting fiscalize() convenience.
 */
class FiscalizationManagerTest extends TestCase
{
    const MXIK = '00702001001000001';

    private function receipt()
    {
        return Receipt::sale('order-1', [
            new ReceiptItem('Subscription', self::MXIK, 12000000, 1),
        ])->payByCard();
    }

    /** @test */
    public function it_resolves_the_configured_default_driver()
    {
        $manager = new FiscalizationManager(['default' => 'null']);

        $this->assertInstanceOf(NullDriver::class, $manager->driver());
        $this->assertSame('null', $manager->defaultDriver());
    }

    /** @test */
    public function default_driver_falls_back_to_null_when_unset()
    {
        $manager = new FiscalizationManager([]);
        $this->assertSame('null', $manager->defaultDriver());
        $this->assertInstanceOf(NullDriver::class, $manager->driver());
    }

    /** @test */
    public function it_caches_resolved_driver_instances()
    {
        $manager = new FiscalizationManager(['default' => 'null']);
        $this->assertSame($manager->driver('null'), $manager->driver('null'));
    }

    /** @test */
    public function an_unknown_driver_throws()
    {
        $manager = new FiscalizationManager([]);

        $this->expectException(FiscalizationException::class);
        $manager->driver('does-not-exist');
    }

    /** @test */
    public function extend_registers_a_custom_driver()
    {
        $manager = new FiscalizationManager([
            'drivers' => ['fake' => ['flag' => 'value']],
        ]);

        $captured = null;
        $manager->extend('fake', function ($config, $http) use (&$captured) {
            $captured = $config;
            return new NullDriver();
        });

        $this->assertInstanceOf(NullDriver::class, $manager->driver('fake'));
        $this->assertSame(['flag' => 'value'], $captured); // per-driver config is passed through
    }

    /** @test */
    public function fiscalize_dispatches_the_success_event()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new FiscalizationManager(['default' => 'null'], null, $dispatcher);

        $result = $manager->fiscalize($this->receipt());

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $dispatcher->ofType(ReceiptFiscalized::class));
        $this->assertCount(0, $dispatcher->ofType(FiscalizationFailed::class));
        $this->assertSame('null', $dispatcher->ofType(ReceiptFiscalized::class)[0]->driver);
    }

    /** @test */
    public function fiscalize_converts_driver_faults_to_a_failure_result_and_event()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new FiscalizationManager([], null, $dispatcher);

        // A custom driver that throws a transport fault.
        $manager->extend('boom', function () {
            return new class implements FiscalDriver {
                public function fiscalize(Receipt $receipt)
                {
                    throw new FiscalizationException('connection refused');
                }
                public function name()
                {
                    return 'boom';
                }
            };
        });

        $result = $manager->fiscalize($this->receipt(), 'boom');

        $this->assertInstanceOf(FiscalResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertSame('connection refused', $result->errorMessage());
        $this->assertCount(1, $dispatcher->ofType(FiscalizationFailed::class));
    }

    /** @test */
    public function fiscalize_propagates_invalid_receipt_errors_instead_of_swallowing_them()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new FiscalizationManager(['default' => 'null'], null, $dispatcher);

        // A structurally invalid receipt (bad MXIK) is a programmer error and must
        // surface, exactly like the direct driver()->fiscalize() path — not be
        // hidden behind a FiscalResult::failure().
        $bad = Receipt::sale('order-1', [new ReceiptItem('Item', '123', 1000, 1)]);

        try {
            $manager->fiscalize($bad);
            $this->fail('Expected InvalidReceiptException to propagate.');
        } catch (InvalidReceiptException $e) {
            $this->assertCount(0, $dispatcher->ofType(FiscalizationFailed::class));
        }
    }

    /** @test */
    public function fiscalize_through_the_ofd_driver_converts_a_transport_fault_to_a_failure_and_event()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new FiscalizationManager(
            ['default' => 'ofd', 'drivers' => ['ofd' => ['endpoint' => 'https://ofd.example/r', 'token' => 't']]],
            new ThrowingHttpClient(),
            $dispatcher
        );

        $result = $manager->fiscalize($this->receipt());

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('connection refused', $result->errorMessage());
        $this->assertCount(1, $dispatcher->ofType(FiscalizationFailed::class));
    }
}
