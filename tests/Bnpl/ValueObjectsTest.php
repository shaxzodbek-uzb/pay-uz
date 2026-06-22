<?php

namespace Goodoneuz\PayUz\Tests\Bnpl;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Contract;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus;

/**
 * BNPL value objects: enum classification and the response-code helpers.
 */
class ValueObjectsTest extends TestCase
{
    /** @test */
    public function eligibility_classifies_each_status_group()
    {
        $this->assertTrue((new Eligibility(['status' => 4]))->isEligible());
        $this->assertFalse((new Eligibility(['status' => 1]))->isEligible());

        foreach ([0, 1, 2, 5, 10, 11, 12] as $code) {
            $this->assertTrue((new Eligibility(['status' => $code]))->mustRegister(), 'code='.$code);
        }
        foreach ([8, 9, 13, 14, 403] as $code) {
            $this->assertTrue((new Eligibility(['status' => $code]))->isBlocked(), 'code='.$code);
        }
        $this->assertTrue((new Eligibility(['status' => 4, 'is_in_black_list' => true]))->isBlocked());
    }

    /** @test */
    public function contract_result_from_response_computes_ok_and_retryable()
    {
        $ok = ContractResult::fromResponse(['response_code' => 0, 'data' => ['client_act_pdf' => 'x']], 200);
        $this->assertTrue($ok->isOk());
        $this->assertSame('x', $ok->actPdfUrl());
        $this->assertFalse($ok->isRetryable());

        $this->assertFalse(ContractResult::fromResponse(['response_code' => 4009], 400)->isOk());
        $this->assertTrue(ContractResult::fromResponse(['response_code' => 1000], 400)->isRetryable());
        $this->assertTrue(ContractResult::fromResponse(['response_code' => 4005], 502)->isRetryable());
    }

    /** @test */
    public function contract_status_helpers()
    {
        $this->assertTrue((new ContractStatus(['contract_status' => 1]))->isActive());
        $this->assertTrue((new ContractStatus(['contract_status' => 5]))->isCancelled());
        $this->assertTrue((new ContractStatus(['contract_status' => 9]))->isClosed());
        $this->assertTrue((new ContractStatus(['contract_status' => 3]))->isOverdue());
    }

    /** @test */
    public function contract_exposes_both_ids()
    {
        $contract = new Contract(['contract_id' => 9, 'order_id' => 5, 'total' => 1200000]);
        $this->assertSame(9, $contract->contractId());
        $this->assertSame(5, $contract->orderId());
        $this->assertSame(1200000, $contract->totalTiyin());
    }
}
