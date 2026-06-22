<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;

/**
 * FiscalResult value object: success/failure factories and the persistable array.
 */
class FiscalResultTest extends TestCase
{
    /** @test */
    public function success_carries_the_fiscal_attributes()
    {
        $result = FiscalResult::success([
            'receipt_id'  => 'r-1',
            'fiscal_sign' => 'FP123',
            'qr'          => 'https://ofd.soliq.uz/check/FP123',
            'receipt_url' => 'https://ofd.soliq.uz/check/FP123',
            'terminal_id' => 'T-9',
            'raw'         => ['ok' => true],
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('r-1', $result->receiptId());
        $this->assertSame('FP123', $result->fiscalSign());
        $this->assertSame('https://ofd.soliq.uz/check/FP123', $result->qr());
        $this->assertSame('T-9', $result->terminalId());
        $this->assertSame(['ok' => true], $result->raw());
    }

    /** @test */
    public function failure_carries_the_error_and_is_not_successful()
    {
        $result = FiscalResult::failure('OFD rejected receipt', 422, ['error' => 'bad mxik']);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('OFD rejected receipt', $result->errorMessage());
        $this->assertSame(422, $result->errorCode());
        $this->assertSame(['error' => 'bad mxik'], $result->raw());
    }

    /** @test */
    public function to_array_is_persistable_and_omits_raw()
    {
        $array = FiscalResult::success(['receipt_id' => 'r-1', 'fiscal_sign' => 'FP'])->toArray();

        $this->assertSame('r-1', $array['receipt_id']);
        $this->assertSame('FP', $array['fiscal_sign']);
        $this->assertTrue($array['success']);
        $this->assertArrayNotHasKey('raw', $array);
    }
}
