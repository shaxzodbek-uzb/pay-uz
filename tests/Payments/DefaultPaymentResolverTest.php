<?php

namespace Goodoneuz\PayUz\Tests\Payments;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Payments\DefaultPaymentResolver;

/**
 * The fallback resolver reproduces the old published-hook defaults: key is the
 * model id, amounts are accepted, responses pass through, and a missing
 * App\Models\User resolves to null (no host model in the package test harness).
 */
class DefaultPaymentResolverTest extends TestCase
{
    private function resolver()
    {
        return new DefaultPaymentResolver();
    }

    /** @test */
    public function convert_model_to_key_returns_the_model_id()
    {
        $this->assertSame(15, $this->resolver()->convertModelToKey((object) ['id' => 15]));
    }

    /** @test */
    public function convert_model_to_key_returns_null_without_an_id()
    {
        $this->assertNull($this->resolver()->convertModelToKey((object) ['name' => 'x']));
        $this->assertNull($this->resolver()->convertModelToKey('not-an-object'));
    }

    /** @test */
    public function convert_key_to_model_returns_null_when_the_user_model_is_absent()
    {
        // App\Models\User is not autoloadable in the package test harness.
        $this->assertNull($this->resolver()->convertKeyToModel(123));
    }

    /** @test */
    public function is_proper_model_and_amount_defaults_to_true()
    {
        $this->assertTrue($this->resolver()->isProperModelAndAmount((object) [], 999));
    }

    /** @test */
    public function before_response_passes_the_response_through_unchanged()
    {
        $response = ['click_trans_id' => 1, 'merchant_trans_id' => 2];

        $this->assertSame($response, $this->resolver()->beforeResponse('Click@Prepare', ['p' => 1], $response));
    }
}
