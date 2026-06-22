<?php

namespace Goodoneuz\PayUz\Tests\Bnpl;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Bnpl\BnplManager;
use Goodoneuz\PayUz\Bnpl\Contracts\BnplDriver;
use Goodoneuz\PayUz\Bnpl\Drivers\NullBnplDriver;
use Goodoneuz\PayUz\Bnpl\Events\ContractCreated;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Contract;
use Goodoneuz\PayUz\Bnpl\Events\ContractConfirmed;
use Goodoneuz\PayUz\Bnpl\Events\ContractCancelled;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus;
use Goodoneuz\PayUz\Tests\Support\RecordingDispatcher;
use Goodoneuz\PayUz\Bnpl\Exceptions\BnplException;

/**
 * Manager: driver resolution + extend(), and the event-emitting lifecycle helpers.
 */
class BnplManagerTest extends TestCase
{
    private function manager(RecordingDispatcher $dispatcher = null)
    {
        return new BnplManager(['default' => 'null'], null, $dispatcher);
    }

    /** @test */
    public function it_resolves_the_default_driver_and_unknown_throws()
    {
        $this->assertInstanceOf(NullBnplDriver::class, $this->manager()->driver());
        $this->assertSame('null', (new BnplManager([]))->defaultDriver());

        $this->expectException(BnplException::class);
        (new BnplManager([]))->driver('nope');
    }

    /** @test */
    public function extend_registers_a_custom_driver()
    {
        $manager = new BnplManager(['drivers' => ['x' => ['k' => 'v']]]);
        $captured = null;
        $manager->extend('x', function ($config, $http) use (&$captured) {
            $captured = $config;
            return new NullBnplDriver();
        });

        $this->assertInstanceOf(NullBnplDriver::class, $manager->driver('x'));
        $this->assertSame(['k' => 'v'], $captured);
    }

    /** @test */
    public function create_contract_emits_contract_created()
    {
        $dispatcher = new RecordingDispatcher();
        $contract = $this->manager($dispatcher)->createContract(1, '12 Default', [['price' => 1200000, 'amount' => 1]]);

        $this->assertSame(1, $contract->contractId());
        $this->assertCount(1, $dispatcher->ofType(ContractCreated::class));
        $this->assertSame('null', $dispatcher->ofType(ContractCreated::class)[0]->driver);
    }

    /** @test */
    public function confirm_and_cancel_emit_their_events_on_success()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = $this->manager($dispatcher);

        $this->assertTrue($manager->confirm(1)->isOk());
        $this->assertCount(1, $dispatcher->ofType(ContractConfirmed::class));

        $this->assertTrue($manager->cancel(1)->isOk());
        $this->assertCount(1, $dispatcher->ofType(ContractCancelled::class));
    }

    /** @test */
    public function confirm_and_cancel_do_not_emit_when_the_result_is_not_ok()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = new BnplManager(['default' => 'failing'], null, $dispatcher);
        $manager->extend('failing', function () {
            return new class implements BnplDriver {
                public function checkEligibility($phone) { return new Eligibility(); }
                public function calculate($buyerId, array $items) { return []; }
                public function createContract($buyerId, $period, array $items, $extOrderId = null, $returnUrl = null) { return new Contract(); }
                public function confirm($contractId) { return new ContractResult(false, ContractResult::CODE_WRONG_STATUS); }
                public function cancel($orderId) { return new ContractResult(false, ContractResult::CODE_WRONG_STATUS); }
                public function status($contractId) { return new ContractStatus(); }
                public function name() { return 'failing'; }
            };
        });

        $this->assertFalse($manager->confirm(9)->isOk());
        $this->assertFalse($manager->cancel(9)->isOk());

        $this->assertCount(0, $dispatcher->ofType(ContractConfirmed::class));
        $this->assertCount(0, $dispatcher->ofType(ContractCancelled::class));
    }

    /** @test */
    public function eligibility_calculate_and_status_pass_through_without_events()
    {
        $dispatcher = new RecordingDispatcher();
        $manager = $this->manager($dispatcher);

        $elig = $manager->checkEligibility('998900000000');
        $this->assertTrue($elig->isEligible());

        $plans = $manager->calculate(1, [['price' => 1200000, 'amount' => 1]]);
        $this->assertSame(1200000, $plans[0]->totalTiyin());

        $this->assertTrue($manager->status(1)->isActive());
        $this->assertCount(0, $dispatcher->events); // read-only ops emit nothing
    }
}
