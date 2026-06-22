<?php

namespace Goodoneuz\PayUz\Tests\Bnpl;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Bnpl\Drivers\NullBnplDriver;

/**
 * The no-op BNPL driver simulates the installment happy path with no network.
 */
class NullBnplDriverTest extends TestCase
{
    /** @test */
    public function it_walks_the_full_installment_flow()
    {
        $driver = new NullBnplDriver();

        $elig = $driver->checkEligibility('998900000000');
        $this->assertTrue($elig->isEligible());

        $plans = $driver->calculate($elig->buyerId(), [['price' => 1200000, 'amount' => 1]]);
        $this->assertCount(1, $plans);
        $this->assertSame(1200000, $plans[0]->totalTiyin());
        $this->assertSame(100000, $plans[0]->monthlyTiyin()); // 1_200_000 / 12

        $contract = $driver->createContract($elig->buyerId(), $plans[0]->tariffId(), [['price' => 1200000, 'amount' => 1]]);
        $this->assertSame(1, $contract->contractId());

        $this->assertTrue($driver->confirm($contract->contractId())->isOk());
        $this->assertTrue($driver->status($contract->contractId())->isActive());
        $this->assertTrue($driver->cancel($contract->orderId())->isOk());
        $this->assertSame('null', $driver->name());
    }
}
